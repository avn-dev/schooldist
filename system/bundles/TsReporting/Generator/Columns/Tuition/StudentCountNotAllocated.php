<?php

namespace TsReporting\Generator\Columns\Tuition;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use TsReporting\Generator\Columns\Booking\StudentCount;
use TsReporting\Generator\Scopes\Booking\CourseScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;
use TsReporting\Traits\ColumnReduceTrait;

/**
 * Ticket #15385 – GLS - Teilnehmer und Raumbedarf - Teil 1
 *
 * Diese Spalte zählt alle Schüler, die im Zeitraum mit keiner Lektion in der Klassenplanung zugewiesen sind.
 * Sobald es eine Lektion gibt, wird der Schüler nicht mehr gezählt.
 * Bei Lektionskursen gibt es hier eine spezielle Logik, da es keine Lektionen pro Woche gibt.
 *
 * TODO Beschreibung:
 * Diese Spalte zählt alle Schüler einmalig, die im Zeitraum pro Kurs nicht mit mind. einer Lektion zugewiesen sind.
 *
 * TODO Spalte sollte nur mit Leistungszeitraum funktionieren; macht bei Erstellungsdatum keinen Sinn
 */
class StudentCountNotAllocated extends StudentCount
{
	use ColumnReduceTrait;

	protected array $availableGroupings = [
		\TsReporting\Generator\Groupings\Aggregated::class,
		\TsReporting\Generator\Groupings\Booking\Agency::class,
		\TsReporting\Generator\Groupings\Booking\Booking::class,
		\TsReporting\Generator\Groupings\Booking\Course::class,
		\TsReporting\Generator\Groupings\Booking\Gender::class,
		\TsReporting\Generator\Groupings\Booking\Inbox::class,
		\TsReporting\Generator\Groupings\Booking\Nationality::class,
		\TsReporting\Generator\Groupings\Booking\SalesPerson::class,
		\TsReporting\Generator\Groupings\Booking\StudentStatus::class,
		\TsReporting\Generator\Groupings\School::class,
		\TsReporting\Generator\Groupings\Period::class,
	];

	public function getTitle(?array $varying = null): string
	{
		return $this->t('Anzahl der Schüler (nicht zu mind. 1 Klasse zugewiesen)');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		parent::build($builder, $values);

		$builder->requireScope(CourseScope::class);
		$builder->requireScope(function (QueryBuilder $builder) {
			$builder
				->leftJoin('ts_tuition_courses_programs_services as ts_tcps', function (JoinClause $join) {
					$join->on('ts_tcps.program_id', 'ts_ijc.program_id');
					$join->where('ts_tcps.type', \TsTuition\Entity\Course\Program\Service::TYPE_COURSE);
					$join->where('ts_tcps.active', 1);
				})
				// TODO korrekt?
				->leftJoin('ts_inquiries_journeys_courses_lessons_contingent as ts_ijclc', function (JoinClause $join) {
					$join->on('ts_ijclc.journey_course_id', 'ts_ijc.id');
					$join->where('ts_ijclc.program_service_id', 'ts_tcps.id');
				})
				->leftJoin('kolumbus_tuition_courses as ktc_combination_course', function (JoinClause $join) {
					$join->on('ktc_combination_course.id', 'ts_tcps.type_id');
					$join->where('ktc_combination_course.per_unit', '!=', \Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT);
					$join->where('ktc_combination_course.active', 1);
				})
				->leftJoin('kolumbus_tuition_blocks_inquiries_courses as ktbic', function (JoinClause $join) {
					$join->on('ktbic.inquiry_course_id', 'ts_ijc.id');
					$join->on('ktbic.program_service_id', 'ts_tcps.id');
					$join->where('ktbic.active', 1);

					$join->join('kolumbus_tuition_blocks as ktb', function (JoinClause $join) {
						$join->on('ktb.id', 'ktbic.block_id');
						$join->where('ktb.active', 1);
					});
					$join->join('kolumbus_tuition_blocks_days as ktbd', 'ktbd.block_id', 'ktb.id');
					$join->join('kolumbus_tuition_classes as ktcl', function (JoinClause $join) {
						$join->on('ktcl.id', 'ktb.class_id');
						$join->where('ktcl.active', 1);
					});
				});
		});

		$builder
			->addSelect('tc_c.id as contact_id')
			->addSelect('ts_ijc.units')
			->addSelect('cdb2.course_startday')
			->addSelect('ktc_combination_course.per_unit')
			// TODO korrekt?
			->addSelect('ts_ijclc.lessons')
			->addSelect('ktc_combination_course.lesson_duration')
			->selectRaw("GROUP_CONCAT(DISTINCT CONCAT(ktb.id, ',', ktb.week, ',', ktbic.lesson_duration) SEPARATOR ';') as block_allocations")
			->selectRaw("GROUP_CONCAT(DISTINCT CONCAT(ktb.id, ',', ktbd.day) SEPARATOR ';') block_days")
			->selectRaw("GROUP_CONCAT(DISTINCT ktc_combination_course.name_short) label_course");

		// Wird durch prepare() praktisch zu ts_ijc.id
		$builder->groupBy(['tc_c.id', 'ts_ijc.id', 'ktc_combination_course.id']);
	}

	public function prepare(Collection $result, ValueHandler $values): Collection
	{
		$students = $result->reduce(function (array $carry, array $row) use ($values) {

			$key = $this->buildGroupingRowKey($row, $row['contact_id']);
			$row['lesson_duration'] = (float)$row['lesson_duration'];

			// Ein Schüler soll weiterhin nur einmal gezählt werden, aber der Query holt Rows per Kontakt, Kursbuchung, Teilkurs
			if ($this->checkStudentCourseAllocation($row, $values)) {
				if (!isset($carry[$key])) {
					$carry[$key] = $row;
				}

				$carry[$key]['courses'][] = $row['label_course'];
			}

			return $carry;
		}, []);

		// Labels der Teilkurse mergen
		$students = collect($students)->map(function (array $row) {
			$row['label'] = sprintf('%s (%s)', $row['label'], join(', ', $row['courses']));
			return $row;
		});

		return $students;
	}

	/**
	 * Prüfen, ob der Schüler (mit dieser Kursbuchung + Teilkurs) gezählt wird oder nicht
	 */
	private function checkStudentCourseAllocation(array $row, ValueHandler $values): bool {

		// Wenn es keine Zuweisungen gibt, ist der Schüler definitiv nicht zugewiesen
		if (empty($row['block_allocations'])) {
			return true;
		}

		// Das sollte eigentlich nicht vorkommen, aber ein Import kann das natürlich umgehen - so funktioniert auch die Anwesenheit generell nicht
		if (empty($row['lesson_duration'])) {
			return false;
		}

		// Da es in der Statistik flexible Zeiträume gibt, muss die wöchentliche Klassenplanung auf jeden Tag runtergebrochen werden
		$lessonsPerDay = $this->calculateLessonsPerDay($row);

		$lessonCounter = 0;
		foreach ($lessonsPerDay as $lessonPerDay) {

			[$date, $lessons] = $lessonPerDay;

			// In der aktuellen Woche ist mindestens eine Lektion zugewiesen
			if (
				$lessons > 0 &&
				$date >= $values->getPeriod()->getStartDate() &&
				$date <= $values->getPeriod()->getEndDate()
			) {
				return false;
			}

			// Lektionskurse: Wenn alle Lektionen bereits zugewiesen wurden, soll der Schüler in keiner Woche mehr auftauchen
			$lessonCounter += $lessons;
			if (
				$row['per_unit'] &&
				$lessonCounter >= $row['units']
			) {
				return false;
			}

		}

		return true;

	}

	/**
	 * Lektionsdauer pro Tag ausrechnen, über den kompletten Zuweisungszeitraum
	 *
	 * @param array $row
	 * @return array
	 */
	private function calculateLessonsPerDay(array $row): array {

		// Tage separat, da man GROUP_CONCAT nicht verschachteln kann
		$blockDays = [];
		foreach (explode(';', $row['block_days']) as $dayData) {
			[$id, $day] = explode(',', $dayData);
			$blockDays[$id][] = (int)$day;
		}

		$blockDayLessons = [];
		foreach (explode(';', $row['block_allocations']) as $blockData) {
			[$id, $week, $allocated] = explode(',', $blockData);

			$lessonsPerDay = $allocated / count($blockDays[$id]) / $row['lesson_duration'];

			foreach ($blockDays[$id] as $day) {
				// Nicht Carbon, weil Carbon langsam ist bei hunderttausenden new/parse
				$date = \DateTime::createFromFormat('Y-m-d', $week);
				$date = \Ext_Thebing_Util::getRealDateFromTuitionWeek($date, $day, $row['course_startday']);
				$blockDayLessons[$date->getTimestamp()] = [$date, $lessonsPerDay];
			}
		}

		uksort($blockDayLessons, function (int $timestamp1, int $timestamp2) {
			return $timestamp1 > $timestamp2;
		});

		return $blockDayLessons;

	}
}