<?php

namespace TsTuition\Service;

use Illuminate\Support\Arr;
use Ts\Entity\Inquiry\Journey\Course\LessonsContingent;
use Core\Entity\ParallelProcessing\Stack;
use TsTuition\Enums\LessonsUnit;

readonly class CourseLessonsContingentService
{
	public function __construct(
		private LessonsContingent $lessonsContingent
	) {}

	public function lazyUpdate(int $columns = null, int $prio = 1): void
	{
		if (!$this->lessonsContingent->exist()) {
			throw new \RuntimeException('Cannot lazy update lessons contingent that does not exist');
		}

		$payload = [
			'id' => $this->lessonsContingent->id,
			'columns' => $columns
		];

		Stack::getRepository()->writeToStack('ts-tuition/lesson-contingent', $payload, $prio);
	}

	public function update(int $columns = null)
	{
		if (!$this->lessonsContingent->exist() || $columns === null) {
			$columns = LessonsContingent::ABSOLUTE | LessonsContingent::USED | LessonsContingent::CANCELLED;
		}

		if ($columns & LessonsContingent::ABSOLUTE) {
			[$absolute, $lessons, $unit, $weeks] = $this->getAbsoluteLessons();
			$this->lessonsContingent->absolute = $absolute;
			$this->lessonsContingent->lessons = $lessons;
			$this->lessonsContingent->lessons_unit = $unit->value;
			$this->lessonsContingent->weeks = $weeks;
		}
		if ($columns & LessonsContingent::USED) {
			$this->lessonsContingent->used = $this->getUsedLessons();
		}
		if ($columns & LessonsContingent::CANCELLED) {
			$this->lessonsContingent->cancelled = $this->getCancelledLessons();
		}

		return $this->lessonsContingent;
	}

	private function getAbsoluteLessons(): array
	{
		$journeyCourse = $this->lessonsContingent->getJourneyCourse();
		$programService = $this->lessonsContingent->getProgramService();
		$service = $programService->getService();

		if (!$service instanceof \Ext_Thebing_Tuition_Course) {
			throw new \RuntimeException(sprintf('Program service is no course [%d]', $service->id));
		}

		if (!$service->canHaveLessons()) {
			return [0, null, null];
		}

		$unit = LessonsUnit::from($service->lessons_unit);

		$lessons = ($service->isPerUnitCourse() && $service->id == $journeyCourse->course_id)
			// Wenn der Kurs nicht Bestandteil einer Kombinationskursbuchung ist
			? $journeyCourse->units
			: Arr::first($service->lessons_list);

		$weeks = ($journeyCourse->getCourse()->isProgram())
			? $programService->getWeeks()
			: $journeyCourse->weeks;

		$absolute = $lessons;
		if ($unit->isPerWeek()) {
			// Bei "Pro Woche" mit den Wochen der Buchung multiplizieren
			$absolute = $lessons * $weeks;
		}

		return [$absolute, $lessons, $unit, $weeks];
	}

	private function getUsedLessons(): float
	{
		/*$sql = "
			SELECT
			 	SUM(`ktt`.`lessons`)
		 	FROM
			    `kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
			    `kolumbus_tuition_blocks` `ktb` ON
			    	 `ktb`.`id` = `ktbic`.`block_id` AND
			    	 `ktb`.`active` = 1 INNER JOIN
			    `kolumbus_tuition_templates` `ktt` ON
			    	 `ktt`.`id` = `ktb`.`template_id` INNER JOIN 
			    `kolumbus_tuition_blocks_days` `ktbd` ON
			     	`ktbd`.`block_id` = `ktb`.`id` LEFT JOIN 
				`ts_tuition_blocks_daily_units` `ts_tbdu` ON
					`ts_tbdu`.`block_id` = `ktb`.`id` AND
					`ts_tbdu`.`day` = `ktbd`.`day`
			WHERE
			    `ktbic`.`active` = 1 AND
			    `ktbic`.`inquiry_course_id` = :journey_course_id AND
			    `ktbic`.`program_service_id` = :program_service_id AND
			    (
			        `ts_tbdu`.`state` IS NULL OR
			        NOT `ts_tbdu`.`state` & '".Unit::STATE_CANCELLED."'
			    )
		";*/

		$sql = "
			SELECT
				SUM(`allocated_lessons`)
			FROM
			    `ts_inquiries_journeys_courses_tuition_index` 
			WHERE
			    `journey_course_id` = :journey_course_id AND
			    `program_service_id` = :program_service_id
		";

		$usedLessons = (float)\DB::getQueryOne($sql, [
			'journey_course_id' => $this->lessonsContingent->journey_course_id,
			'program_service_id' => $this->lessonsContingent->program_service_id
		]);

		return $usedLessons;
	}

	private function getCancelledLessons(): float
	{
		/*$sql = "
			SELECT
			 	SUM(`ktt`.`lessons`) 
		 	FROM
			    `kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
			    `kolumbus_tuition_blocks` `ktb` ON
			    	 `ktb`.`id` = `ktbic`.`block_id` AND
			    	 `ktb`.`active` = 1 INNER JOIN
			    `kolumbus_tuition_templates` `ktt` ON
			    	 `ktt`.`id` = `ktb`.`template_id` INNER JOIN 
			    `kolumbus_tuition_blocks_days` `ktbd` ON
			     	`ktbd`.`block_id` = `ktb`.`id` INNER JOIN 
				`ts_tuition_blocks_daily_units` `ts_tbdu` ON
					`ts_tbdu`.`block_id` = `ktb`.`id` AND
					`ts_tbdu`.`day` = `ktbd`.`day` AND
					`ts_tbdu`.`state` & '".Unit::STATE_CANCELLED."'
			WHERE
			    `ktbic`.`active` = 1 AND
			    `ktbic`.`inquiry_course_id` = :journey_course_id AND
			    `ktbic`.`program_service_id` = :program_service_id
		";*/

		$sql = "
			SELECT
				SUM(`cancelled_lessons`)
			FROM
			    `ts_inquiries_journeys_courses_tuition_index` 
			WHERE
			    `journey_course_id` = :journey_course_id AND
			    `program_service_id` = :program_service_id
		";

		$cancelledLessons = (float)\DB::getQueryOne($sql, [
			'journey_course_id' => $this->lessonsContingent->journey_course_id,
			'program_service_id' => $this->lessonsContingent->program_service_id
		]);

		return $cancelledLessons;
	}

	public static function updateBlock(\Ext_Thebing_School_Tuition_Block $block, int $columns = null, int $prio = 0): void
	{
		$allocations = $block->getAllocations();

		foreach ($allocations as $allocation) {

			$journeyCourse = $allocation->getJourneyCourse();

			$service = new self($journeyCourse->getLessonsContingent($allocation->getProgramService()));

			if ($prio > 0) {
				$service->lazyUpdate($columns, $prio);
			} else {
				$service->update($columns);
			}
		}
	}

}