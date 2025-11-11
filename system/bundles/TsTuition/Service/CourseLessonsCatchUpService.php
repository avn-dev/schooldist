<?php

namespace TsTuition\Service;

use Carbon\Carbon;
use Core\Helper\BitwiseOperator;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use TsTuition\Entity\Block\Unit;
use TsTuition\Entity\Course\Program\Service;
use TsTuition\Enums\LessonsUnit;

readonly class CourseLessonsCatchUpService
{
	public function __construct(
		private \Ext_TS_Inquiry_Journey_Course $journeyCourse
	) {}

	public function lazyUpdate(int $prio = 1): void
	{
		$payload = [
			'id' => $this->journeyCourse->id
		];

		\Core\Entity\ParallelProcessing\Stack::getRepository()
			->writeToStack('ts-tuition/course-lessons-catch_up', $payload, $prio);
	}

	public function update(): void
	{
		// Kursverlängerung
		$changed = $this->fill();

		if ($changed) {
			$this->journeyCourse->lock()->save();
		}
	}

	public function fill(): bool
	{
		$programCourses = $this->journeyCourse->getProgram()->getServices(Service::TYPE_COURSE);

		// Originales Enddatum vor der Verlängerung durch Kursausfall
		$originalEnd = $this->getOriginalUntilDate();

		$state = $this->journeyCourse->state;

		// Bereits verlängert?
		$isAlreadyExtended = BitwiseOperator::has($state, \Ext_TS_Inquiry_Journey_Course::STATE_EXTENDED_DUE_CANCELLATION);

		// Höchstes Enddatum nach Kursausfall
		$maxCatchUpEnd = null;

		foreach ($programCourses as $programService) {

			$course = $programService->getService();

			if (!$course->catch_up_on_cancelled_lessons) {
				// Lektionen sollen nicht nachgeholt werden
				continue;
			}

			if (
				$course->automatic_renewal == 1 &&
				$this->journeyCourse->automatic_renewal_cancellation === null
			) {
				// die automatische Kursverlängerung ist noch aktiv und wurde noch nicht gekündigt. Erst mit der Kündigung
				// wird der Kurs verlängert
				$maxCatchUpEnd = null;
				break;
			}

			$canceledUnits = $this->getCancelledUnitsForJourneyCourse();

			if ($canceledUnits->isNotEmpty()) {
				// Ermitteln um wie viele Wochen verlängert werden soll
				$weeks = $this->splitIntoWeeks($programService, $canceledUnits);
				$end = $originalEnd->clone()->modify(sprintf('+%d weeks', $weeks));

				if ($maxCatchUpEnd === null || $end > $maxCatchUpEnd) {
					$maxCatchUpEnd = $end;
				}
			}
		}

		$newEnd = null;
		if ($maxCatchUpEnd === null && $isAlreadyExtended) {
			// Keine Kursausfälle vorhanden oder automatische Kursverlängerung noch aktiv, Kursverlängerung zurücknehmen
			$newEnd = $originalEnd->clone();
		} else if ($maxCatchUpEnd) {
			if (!$isAlreadyExtended || $maxCatchUpEnd->format('Y-m-d') !== $originalEnd->format('Y-m-d')) {
				// Kursausfälle vorhanden, Kurs verlängern
				$newEnd = $maxCatchUpEnd->clone();
			}
		}

		if ($newEnd) {
			// Flag mit originalem Enddatum setzen um dieses wiederherstellen zu können
			if ($newEnd->format('Y-m-d') !== $originalEnd->format('Y-m-d')) {
				BitwiseOperator::add($state, \Ext_TS_Inquiry_Journey_Course::STATE_EXTENDED_DUE_CANCELLATION);
				if ($this->journeyCourse->lessons_catch_up_original_until === null) {
					$this->journeyCourse->lessons_catch_up_original_until = $originalEnd->format('Y-m-d');
				}
			} else {
				BitwiseOperator::remove($state, \Ext_TS_Inquiry_Journey_Course::STATE_EXTENDED_DUE_CANCELLATION);
				$this->journeyCourse->lessons_catch_up_original_until = null;
			}

			$this->journeyCourse->state = $state;
			$this->journeyCourse->until = $newEnd->format('Y-m-d');
			return true;
		}

		return false;
	}

	private function getCancelledUnitsForJourneyCourse(): Collection
	{
		$units = Unit::query()
			->select('ts_tbdu.*')
			->join('kolumbus_tuition_blocks as ktb', function (JoinClause $join) {
				$join->on('ktb.id', '=', 'ts_tbdu.block_id')
					->where('ktb.active', 1);
			})
			->join('kolumbus_tuition_blocks_inquiries_courses as ktbic', function (JoinClause $join) {
				$join->on('ktbic.block_id', '=', 'ktb.id')
					->where('ktbic.active', 1)
					->where('ktbic.inquiry_course_id', $this->journeyCourse->id);
			})
			->where('ts_tbdu.state', '&', Unit::STATE_CANCELLED)
			->get();

		return $units;
	}

	private function splitIntoWeeks(Service $programService, Collection $canceledUnits): int
	{
		$contingent = $this->journeyCourse->getLessonsContingent($programService);

		if (LessonsUnit::from($contingent->lessons_unit)->isAbsolute()) {
			// Nur Wochenkurse müssen auf mehrere Wochen aufgeteilt werden
			return 1;
		}

		$lessons = $canceledUnits->map(fn (Unit $unit) => $unit->getBlock()->getTemplate()->lessons)
			->sum();

		// Bei Wochenkurse ermitteln wie viele Lektionen pro Woche stattfinden und die Nachholtermine dementsprechend
		// auf mehrere Wochen aufteilen
		$weeks = (int)ceil($lessons / (float)$contingent->lessons);

		return $weeks;
	}

	private function getOriginalUntilDate(): Carbon
	{
		if (!empty($original = $this->journeyCourse->lessons_catch_up_original_until)) {
			return Carbon::parse($original);
		}

		return Carbon::parse($this->journeyCourse->until);
	}
}