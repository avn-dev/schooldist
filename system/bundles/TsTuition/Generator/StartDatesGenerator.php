<?php

namespace TsTuition\Generator;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Ts\Dto\CourseStartDate;
use TsTuition\Entity\Course\Program;

/**
 * Generiert die Startdaten eines Kurses basierend auf dessen Einstellungen (Verfügbarkeit)
 *
 * @see CourseStartDate
 */
class StartDatesGenerator {

	/**
	 * @var \Ext_Thebing_Tuition_Course
	 */
	private $course;

	/**
	 * @var \Ext_Thebing_School
	 */
	private $school;

	/**
	 * @var Carbon
	 */
	private $from;

	/**
	 * @var Carbon
	 */
	private $until;

	/**
	 * @var Carbon
	 */
	private $bookableFrom;

	private bool $generateEndDates = false;

	/**
	 * @param \Ext_Thebing_Tuition_Course $course
	 * @param Carbon $from
	 * @param Carbon $until
	 */
	public function __construct(\Ext_Thebing_Tuition_Course $course, Carbon $from, Carbon $until) {

		$this->course = $course;
		$this->school = $course->getSchool();
		$this->from = $from;
		$this->until = $until;

	}

	/**
	 * @return CourseStartDate[]
	 */
	public function generate(): array {

		$this->prepareDates();
		$startDates = $this->getStartDates();
		$startDates = $this->filterStartDates($startDates);

		$notAvailableDates = $this->generateNotAvailableDates();

		return $this->buildStartDates($startDates, $notAvailableDates);

	}

	public function generateNotAvailableDates(): \Illuminate\Support\Collection {
		
		$notAvailableDates = $this->getNotAvailableDates();
		$notAvailableDates = $this->filterStartDates($notAvailableDates);
		
		return $notAvailableDates;
	}
	
	private function prepareDates() {

		$this->from->setTime(0, 0);
		$this->until->setTime(0, 0);

		$this->bookableFrom = $this->from->copy()->addDays($this->getMinBookableDaysAhead());

		// Den Zeitraum auf valid_until begrenzen
		$validUntil = $this->course->getValidUntil();
		if (
			$validUntil !== null &&
			$this->until > $validUntil
		) {
			$this->until = $validUntil;
		}

	}

	private function getStartDates(): \Illuminate\Support\Collection {

		// StartDatesGenerator wird für Programme nicht benutzt, aber dient für das Frontend als Filter
		if ((int)$this->course->per_unit === \Ext_Thebing_Tuition_Course::TYPE_PROGRAM) {
			return $this->course->getPrograms()
				->map(fn(Program $program) => ($programFrom = $program->getFrom()) ? $this->buildCourseStartDate($programFrom) : null)
				->filter(fn ($date) => $date instanceof \Ext_Thebing_Tuition_Course_Startdate);
		}

		switch ($this->course->avaibility) {
			case \Ext_Thebing_Tuition_Course::AVAILABILITY_UNDEFINED:
			case \Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS:
				return $this->getAlwaysAvailableStartDates();
			case \Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS_EACH_DAY:
				return $this->getAlwaysAvailableEachDayStartDates();
			case \Ext_Thebing_Tuition_Course::AVAILABILITY_STARTDATES:
				return $this->course->getConfiguredStartDates();
			case \Ext_Thebing_Tuition_Course::AVAILABILITY_NEVER:
				return collect();
			default:
				throw new \LogicException('Unknown course availability: '.$this->course->avaibility);
		}

	}

	private function getNotAvailableDates(): \Illuminate\Support\Collection {
		return $this->course->getNotAvailableDates();
	}
	
	/**
	 * Irrelevante (abgelaufene) Startdaten direkt rausfiltern
	 *
	 * @param \Ext_Thebing_Tuition_Course_Startdate[] $startDates
	 * @return \Ext_Thebing_Tuition_Course_Startdate[]
	 */
	public function filterStartDates(\Illuminate\Support\Collection $startDates): \Illuminate\Support\Collection {
		
		return $startDates->filter(function (\Ext_Thebing_Tuition_Course_Startdate $startDate) {
			if ($startDate->hasLastStartDate()) {
				return CarbonPeriod::create($this->from, $this->until)->overlaps($startDate->start_date, $startDate->last_start_date);
			} else if ($startDate->hasEndDate()) {
				return CarbonPeriod::create($this->from, $this->until)->overlaps($startDate->start_date, $startDate->end_date);
			}
			return Carbon::parse($startDate->start_date)->between($this->from, $this->until);
		});

	}

	/**
	 * @param \Ext_Thebing_Tuition_Course_Startdate[] $startDates
	 * @return CourseStartDate[]
	 */
	private function buildStartDates(\Illuminate\Support\Collection $startDates, \Illuminate\Support\Collection $notAvailableDates=null) {

		$startDates2 = [];

		foreach ($startDates as $startDate) {

			$start = new Carbon($startDate->start_date);
			$interval = $startDate->period ?: 1;

			// Enddatum ist optional
			if ($startDate->hasLastStartDate()) {
				$end = new Carbon($startDate->last_start_date);
			} else {
				$end = $start->copy();
			}

			$period = CarbonPeriod::start($start)->weeks($interval)->end($end);

			foreach ($period as $date) {

				if (
					$date < $this->bookableFrom ||
					$date > $this->until
				) {
					continue;
				}

				$calculatedMinMax = $this->calculateMinMaxDuration($startDate, $date, $notAvailableDates);

				if (empty($calculatedMinMax)) {
					continue;
				}

				[$minDuration, $maxDuration, $end, $endDates] = $calculatedMinMax;

				$levels = array_map(fn ($levelId) => (int)$levelId, $startDate->levels);
				$courselanguages = array_map(fn ($courselanguageId) => (int)$courselanguageId, $startDate->courselanguages);

				$oDto = new CourseStartDate($date->copy(), $end, $minDuration, $maxDuration, $levels, $courselanguages, $endDates);
				$startDates2[] = $oDto;
			}
		}

		usort($startDates2, fn (CourseStartDate $oDto, CourseStartDate $oDto2) => $oDto->start > $oDto2->start);

		return $startDates2;
	}

	private function calculateMinMaxDuration(\Ext_Thebing_Tuition_Course_Startdate $startDate, Carbon $date, Collection $notAvailableDates = null): ?array
	{
		[$minDuration, $maxDuration] = $startDate->calculateMinMaxDuration($date);
		if($maxDuration < $minDuration) {
			return null;
		}

		$start = new Carbon($startDate->start_date);

		// 3 Tage abziehen, um auf Fr zu kommen
		$subDays = 7 - count(\Ext_Thebing_Util::getCourseWeekDays((int)$start->format('N'))) + 1;
		$end = $date->copy()->addWeeks($maxDuration)->subDays($subDays);

		$endDates = [];
		if ($this->generateEndDates) {
			for ($i = $minDuration; $i <= $maxDuration; $i++) {
				$endDates[$i] = $date->copy()->addWeeks($i)->subDays($subDays);
			}
		}

		if($notAvailableDates !== null) {

			[$blocking, $coming] = $notAvailableDates
				// Nur die herausfiltern die für das Datum überhaupt infrage kommen
				->filter(fn ($notAvailableDate) => (new Carbon($notAvailableDate->end_date))->endOfDay() >= $date)
				// Aufteilen nach blockierenden und zukünftigen Zeiträumen
				->partition(fn ($notAvailableDate) => new Carbon($notAvailableDate->start_date) <= $date);

			if ($blocking->isNotEmpty()) {
				// Datum befindet sich mitten in einem nicht verfügbaren Bereich
				return null;
			}

			// Nächster nicht verfügbare Zeitraum
			$lowestComingNotAvailableDate = $coming
				->map(fn ($notAvailableDate) => new Carbon($notAvailableDate->start_date))
				->filter(fn ($notAvailableDate) => $notAvailableDate >= $date)
				->sort()
				->first();

			if ($lowestComingNotAvailableDate) {
				$diffInWeeks = $lowestComingNotAvailableDate->diffInWeeks($date, true);
				// Schauen wie viele Wochen der Kurs vor dem nicht verfügbaren Zeitraum noch gebucht werden kann
				for ($i = $diffInWeeks; $i > 0; $i--) {
					if ($i >= $minDuration && $i <= $maxDuration) {
						$end = $date->copy()->addWeeks($i)->subDays($subDays);
						if($end < $lowestComingNotAvailableDate) {
							return [$minDuration, $i, $end, $endDates];
						}
					}
				}

				return null;
			}
		}

		return [$minDuration, $maxDuration, $end, $endDates];
	}

	/**
	 * Kurs an jedem Wochentag verfügbar
	 *
	 * @return \Ext_Thebing_Tuition_Course_Startdate[]
	 */
	private function getAlwaysAvailableEachDayStartDates(): \Illuminate\Support\Collection {

		$dates = [];

		$oPeriod = CarbonPeriod::start($this->from)->days(1)->end($this->from->copy()->addDays(6));
		foreach ($oPeriod as $date) {
			$dates[] = $this->buildCourseStartDate($date, $this->until);
		}

		return collect($dates);
	}

	/**
	 * Kurs an jedem Starttag der Schule verfügbar
	 *
	 * @return \Ext_Thebing_Tuition_Course_Startdate[]
	 */
	private function getAlwaysAvailableStartDates(): \Illuminate\Support\Collection {

		$dates = [];
		$nextFromDate = \Ext_Thebing_Util::getNextCourseStartDay($this->from, $this->school->course_startday);
//		$courseWeekdays = \Ext_Thebing_Util::getCourseWeekDays($this->school->course_startday);

		$dates[] = $this->buildCourseStartDate($nextFromDate, $this->until);

//		// Wenn der nächste Starttag nicht mehr buchbar ist, soll der nächste buchbare Tag zur Verfügung stehen
//		// Dieser sollte sich aber noch in der Kurswoche befinden, d.h. bei Starttag Montag und heute Freitag, werden Sa+So ignoriert
//		// Hier wird demnach ein Starttag mit einem einzelnen Tag generiert
//		if (
////			!$this->from->eq($this->bookableFrom) &&
//			!$this->bookableFrom->eq($nextFromDate) && (
//				$this->course->avaibility == \Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS_EACH_DAY ||
//				in_array($this->bookableFrom->isoWeekday(), $courseWeekdays)
//			)
//
//		) {
//			$dates[] = $this->buildCourseStartDate($this->bookableFrom->toDateString(), '0000-00-00');
//		}

		return collect($dates);

	}

	private function buildCourseStartDate(Carbon $from, Carbon $until = null): \Ext_Thebing_Tuition_Course_Startdate {

		$startDate = new \Ext_Thebing_Tuition_Course_Startdate();
		$startDate->start_date = $from->toDateString();
		$startDate->period = 1;
		$startDate->minimum_duration = $this->course->minimum_duration;
		$startDate->maximum_duration = $this->course->maximum_duration;
		$startDate->fix_duration = $this->course->fix_duration;

		if (!$until) {
			$startDate->single_date = 1;
		} else{
			$startDate->last_start_date = $until->toDateString();
		}

		return $startDate;

	}

	private function getMinBookableDaysAhead(): int {

		if ($this->course->frontend_min_bookable_days_ahead !== null) {
			return (int)$this->course->frontend_min_bookable_days_ahead;
		}
		return (int)$this->school->frontend_min_bookable_days_ahead;

	}

	/**
	 * Alle Enddatum pro Startdatum generieren, benötigt für Preislisten (viel Speicherverbrauch)
	 */
	public function setGenerateEndDates(bool $generateEndDates): void {
		$this->generateEndDates = $generateEndDates;
	}
}