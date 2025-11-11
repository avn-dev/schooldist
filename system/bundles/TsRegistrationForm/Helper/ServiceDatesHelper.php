<?php

namespace TsRegistrationForm\Helper;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use TsActivities\Entity\Activity;
use TsRegistrationForm\Generator\CombinationGenerator;

class ServiceDatesHelper {

	/**
	 * @var CombinationGenerator
	 */
	private $combination;

	/**
	 * @var \Ext_TS_Inquiry
	 */
	private $inquiry;

	/**
	 * @param CombinationGenerator $combination
	 * @param \Ext_TS_Inquiry $inquiry
	 */
	public function __construct(CombinationGenerator $combination, \Ext_TS_Inquiry $inquiry) {

		$this->combination = $combination;
		$this->inquiry = $inquiry;

	}

	/**
	 * Unterkunftsdaten aus Kombination holen oder neu generieren (bei Abhängigkeit auf Kurs)
	 *
	 * @return array
	 */
	public function getAccommodationDates(): array {

		$setting = $this->combination->getSettings()['accommodation_availability_start_end'];
		$schoolData = $this->combination->getSchoolData();

		if ($setting === 'accommodation_start_end') {
			$datesMap = $schoolData['accommodation_dates_map'];
			$dates = $schoolData['accommodation_dates'];
		} else {
			// Bei Kursabhängigkeit müssen die Unterkunftsverfügbarkeiten immer dynamisch generiert werden
			[$datesMap, $dates] = $this->generateAccommodationServiceDates($setting, collect($schoolData['accommodations']));
		}

		return [$datesMap, $dates];

	}

	/**
	 * Verfügbarkeitsdaten für spezifische Unterkunftskategorie holen (über Map mit md5-Keys)
	 *
	 * @param int $categoryId
	 * @param array|Collection $map
	 * @param array|Collection $allDates
	 * @return Collection
	 */
	public function getAccommodationDatesForCategory(int $categoryId, $map, $allDates): Collection {

		$key = $map[$categoryId] ?? null;
		$allDates = $allDates[$key] ?? null;

		return collect($allDates);

	}

	/**
	 * Unterkunftsstart- und Enddaten generieren
	 *
	 * Bei Abhängigkeit auf Kursen müssen diese jedes Mal neu generiert werden, ansonsten werden diese statisch generiert.
	 * Dadurch müssen die Parameter auch manuell übergeben werden, damit es keine Endlosschleife bei der Cache-Generierung gibt.
	 *
	 * @see getAccommodationDates()
	 *
	 * @param string $type
	 * @param Collection $accommodations
	 * @return array[]
	 */
	public function generateAccommodationServiceDates(string $type, Collection $accommodations): array {

		$map = [];
		$allDates = [];

		switch ($type) {
			case 'accommodation_start_end':
				// TODO Sollte der Kurs länger als 52 Wochen Laufzeit haben, kann der Zeitraum wiederum fehlen
				$start = Carbon::now('UTC');
				$end = $start->copy()->addYears((int)$this->combination->getSchool()->frontend_years_of_bookable_services);
				break;
			case 'course_period':
			case 'course_period_each_day':
			case 'course_start_end':
				$courseDates = $this->getCoursePeriod();
				if ($courseDates === null) {
					// Keine Kurse, keine Unterkunftsverfügbarkeit
					return [$map, $allDates];
				}
				$start = $courseDates->getStartDate();
				$end = $courseDates->getEndDate();
				break;
			default:
				throw new \InvalidArgumentException(__METHOD__.': Unknown type "'.$type.'"');
		}

		$school = $this->combination->getSchool();

		foreach ($accommodations as $accommodation) {
			$dates = [];
			$category = \Ext_Thebing_Accommodation_Category::getInstance($accommodation['key']);

			$accommodationStartDay = \Ext_TC_Util::convertWeekdayToCarbonWeekday(\Ext_TC_Util::convertWeekdayToInt($category->getAccommodationStart($school)));
			$accommodationStart = $start->clone()->startOfWeek($accommodationStartDay);

			$intervalDays = ($type === 'course_period_each_day') ? 1 : 7;
			$period = $accommodationStart->toPeriod($end, CarbonInterval::days($intervalDays)); // NICHT CarbonInterval::week

			$inclusiveNights = $category->getAccommodationInclusiveNights($school);

			foreach ($period as $startDate) {

				// Unterkunft darf nicht in der Vergangenheit starten können
				if ($startDate < Carbon::now('UTC')) {
					continue;
				}

				$dates[] = [
					'type' => 'start',
					'start' => $startDate->copy()->subDays($category->max_extra_nights_prev)->toDateString(),
					'end' => $startDate->toDateString()
				];
				$startDateEnd = $startDate->copy()->addDays($inclusiveNights);
				$dates[] = [
					'type' => 'end',
					'start' => $startDateEnd->toDateString(),
					'end' => $startDateEnd->copy()->addDays($category->max_extra_nights_after)->toDateString()
				];
			}

			// Unterkunft nur an Start und Ende des Kurses verfügbar
			if (
				!empty($dates) &&
				$type === 'course_start_end'
			) {
				$dates2 = collect($dates);
				$dates = [];
				$dates[] = $dates2->firstWhere('type', 'start');
				$dates[] = $dates2->last(function (array $dateObj) { // lastWhere()
					return $dateObj['type'] === 'end';
				});
			}

			// Datenmenge reduzieren (analog zu Kursstartdaten)
			$key = md5(serialize($dates));
			$map[$category->id] = $key;
			$allDates[$key] = $dates;
		}

		return [$map, $allDates];

	}

	/**
	 * @see \TsRegistrationForm\Generator\FormDataGenerator::generateActivityData()
	 */
	public function generateActivityServiceDates(): array {

		$allDates = [];
		$activities = $this->combination->getSchoolData()['activities'];

		$coursePeriod = $this->getCoursePeriod();
		if ($coursePeriod === null) {
			return [];
		}

		// TODO Start muss ggf. auf Starttag des Kurses korrigiert werden, wenn Kurs noch innerhalb der Woche startet?
		// Aktuell hängt die Aktivität komplett am Kurszeitraum (subject of change)
		// Aktivität hat immer volle Wochen, Kurs aber nicht
		$coursePeriod->setEndDate($coursePeriod->getEndDate()->addDays(2));
		$coursePeriod->setDateInterval('P7D');

		foreach ($activities as $activityData) {

			$activity = Activity::getInstance($activityData['key']);

			// Begrenzte Verfügbarkeit
			$validityPeriods = new PeriodCollection();
			if ($activity->availability === Activity::AVAILABILITY_LIMITED) {
				$validityPeriods = PeriodCollection::make(...array_map(function (\TsActivities\Entity\Activity\Validity $validity) {
					return $validity->getPeriod();
				}, $activity->getValidities()));
			}

			// valid_until gibt es natürlich redundant auch nochmal
			$validUntil = null;
			if (\Core\Helper\DateTime::isDate($activity->valid_until, 'Y-m-d')) {
				$validUntil = Carbon::parse($activity->valid_until);
			}

			$maxWeeks = $coursePeriod->count();
			$dates = [];
			foreach ($coursePeriod as $date) {

				// Um die mögliche maximale Woche für dieses Startdatum zu ermitteln, muss jede Woche geprüft werden
				$week = 0;
				while ($week < $maxWeeks) {

					$period = Period::make($date, $date->copy()->addWeeks($week));
					if (
						(
							$validUntil !== null &&
							$period->includedEnd() > $validUntil
						) ||
						(
							!$validityPeriods->isEmpty() &&
							$validityPeriods->overlapAll(new PeriodCollection($period))->isEmpty()
						)
					) {
						break;
					}

					$week++;
				}

				$maxWeeks--;
				if ($week === 0) {
					continue;
				}

				$dates[] = [
					//'type' => 'duration',
					'start' => $date->toDateString(),
					'min' => 1,
					'max' => $week
				];

			}

			$allDates[$activityData['key']] = $dates;

		}

		return $allDates;

	}

	/**
	 * Standardzeitraum für Unterkunft: Wird gewählt, wenn die Unterkunft entweder ausgewählt wird oder die Verfügbarkeiten nicht mehr passen
	 *
	 * Aktuell passiert das hier unabhängig der gewählten Unterkunft, da die Verfügbarkeiten unterschiedlich sein können. Sollten die hier generierten
	 * Datumsangaben nicht verfügbar sein, wird das Form gar keinen Default setzen. Dabei erscheint auch eine Warnung in der Konsole.
	 *
	 * TODO "accommodation_start" und "inclusive_nights" kann jetzt pro Kategorie unterschiedlich sein, muss das hier nicht immer berücksichtigt werden?
	 *
	 * @return CarbonPeriod|null
	 */
	public function getDefaultAccommodationPeriod(\Ext_Thebing_Accommodation_Category $category = null): ?CarbonPeriod {

		$coursePeriod = $this->getCoursePeriod();
		if ($coursePeriod === null) {
			return null;
		}

		$school = $this->combination->getSchool();

		$accommodationStart = ($category) ? $category->getAccommodationStart($school) : $school->accommodation_start;
		$inclusiveNights = ($category) ? $category->getAccommodationInclusiveNights($school) : $school->inclusive_nights;

		$weekday = \Ext_TC_Util::convertWeekdayToCarbonWeekday(\Ext_TC_Util::convertWeekdayToInt($accommodationStart));

		$start = $coursePeriod->getStartDate(); /** @var Carbon $start */
		$start = $start->startOfWeek($weekday);

		$end = $coursePeriod->getEndDate(); /** @var Carbon $end */
		$end = $end->startOfWeek($weekday)->addDays($inclusiveNights);

		if ($start < Carbon::now('UTC')) {
			$start->addWeek();
		}

		return $start->toPeriod($end);

	}

	/**
	 * Zeitraum über alle Kurse
	 *
	 * @return CarbonPeriod|null
	 */
	public function getCoursePeriod(): ?CarbonPeriod {

		$journey = $this->inquiry->getJourney();

		$courseDates = new Collection();
		foreach ($journey->getCoursesAsObjects() as $course) {
			$courseDates->push(new Carbon($course->from, 'UTC'));
			$courseDates->push(new Carbon($course->until, 'UTC'));
		}

		if ($courseDates->isEmpty()) {
			return null;
		}

		return CarbonPeriod::create($courseDates->min(), $courseDates->max());

	}

	/**
	 * Zeitraum über relevante Leistungen (wird für Versicherungszeitraum und Aktivitäten benötigt)
	 *
	 * @return CarbonPeriod|null
	 */
	public function getServicePeriod(): ?CarbonPeriod {

		if (
			$this->inquiry->exist() &&
			($from = $this->inquiry->getServiceFrom(true)) &&
			($until = $this->inquiry->getServiceUntil(true))
		) {
			return CarbonPeriod::create($from, $until);
		}

		$serviceDates = new Collection();
		$journey = $this->inquiry->getJourney();

		$courseDates = $this->getCoursePeriod();
		if ($courseDates !== null) {
			$serviceDates->push($courseDates->getStartDate());
			$serviceDates->push($courseDates->getEndDate());
		}

		foreach ($journey->getAccommodationsAsObjects() as $accommodation) {
			$serviceDates->push(new Carbon($accommodation->from, 'UTC'));
			$serviceDates->push(new Carbon($accommodation->until, 'UTC'));
		}

		if ($serviceDates->isEmpty()) {
			return null;
		}

		return CarbonPeriod::create($serviceDates->min(), $serviceDates->max());

	}

	/**
	 * Wenn Kurse Unterkunftskombinationen eingestellt haben: Kleinster gemeinsamer Nenner über ALLE ausgewählten Kurse
	 *
	 * Das JS zeigt zwar die Unterkunftsoptionen nicht mehr an, aber die Abwahl (+ Notification) und Validierung laufen serverseitig.
	 *
	 * @param string $combinationKey
	 * @return bool
	 */
	public function checkCourseAccommodationCombination(string $combinationKey): bool {

		$courses = collect($this->inquiry->getJourney()->getCoursesAsObjects());

		return $courses->every(function (\Ext_TS_Inquiry_Journey_Course $course) use ($combinationKey) {
			$frontendCourse = $course->transients['form_course']; /** @var \TsRegistrationForm\Dto\FrontendCourse $frontendCourse */
			if (empty($frontendCourse->accommodations)) {
				return true;
			}
			return in_array($combinationKey, $frontendCourse->accommodations);
		});

	}

	/**
	 * CarbonPeriod an JSON würde alle Iterator-Ergebnisse als Array erzeugen
	 *
	 * @param CarbonPeriod|null $period
	 * @return array
	 */
	public function convertPeriodForJson(CarbonPeriod $period = null): array {

		if ($period === null) {
			return [];
		}

		return [$period->getStartDate()->toDateString(), $period->getEndDate()->toDateString()];

	}

}