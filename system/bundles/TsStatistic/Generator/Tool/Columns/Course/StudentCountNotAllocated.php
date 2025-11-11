<?php

namespace TsStatistic\Generator\Tool\Columns\Course;

use Carbon\Carbon;
use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Tool\Columns\StudentCount;

/**
 * TODO: Bereits in V2 migriert: \TsReporting\Generator\Columns\Tuition\StudentCountNotAllocated
 *
 * Ticket #15385 – GLS - Teilnehmer und Raumbedarf - Teil 1
 *
 * Diese Spalte zählt alle Schüler, die im Zeitraum mit keiner Lektion in der Klassenplanung zugewiesen sind.
 * Sobald es eine Lektion gibt, wird der Schüler nicht mehr gezählt.
 * Bei Lektionskursen gibt es hier eine spezielle Logik, da es keine Lektionen pro Woche gibt.
 *
 * TODO Beschreibung:
 * Diese Spalte zählt alle Schüler einmalig, die im Zeitraum pro Kurs nicht mit mind. einer Lektion zugewiesen sind.
 */
class StudentCountNotAllocated extends StudentCount {

	protected $basedOn = 'course';

	public function getTitle() {
		return self::t('Anzahl der Schüler (nicht zu mind. 1 Klasse zugewiesen)');
	}

	public function getSelect() {

		$select = parent::getSelect();

		$select .= ",
			`tc_c`.`id` `contact_id`,
			`ts_ijc`.`units`,
			`cdb2`.`course_startday`,
			`ktc_combination_course`.`per_unit`,
			`ts_ijclc`.`lessons`, 
			`ktc_combination_course`.`lesson_duration`,
			GROUP_CONCAT(DISTINCT CONCAT(`ktb`.`id`, ',', `ktb`.`week`, ',', `ktbic`.`lesson_duration`) SEPARATOR ';') `block_allocations`,
			GROUP_CONCAT(DISTINCT CONCAT(`ktb`.`id`, ',', `ktbd`.`day`) SEPARATOR ';') `block_days`,
			GROUP_CONCAT(DISTINCT `ktc_combination_course`.`name_short`) `label_course`
		";

		return $select;

	}

	public function getJoinPartsAdditions() {

		$additions = parent::getJoinPartsAdditions();

		$additions['JOIN_COURSES'] = " LEFT JOIN
			`ts_tuition_courses_programs_services` `ts_tcps` ON
				`ts_tcps`.`program_id` = `ts_ijc`.`program_id` AND
				`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
				`ts_tcps`.`active` = 1 LEFT JOIN
			`ts_inquiries_journeys_courses_lessons_contingent` `ts_ijclc` ON
				`ts_ijclc`.`journey_course_id` = `ts_ijc`.`id` AND
				`ts_ijclc`.`program_service_id` = `ts_tcps`.`id` LEFT JOIN
			`kolumbus_tuition_courses` `ktc_combination_course` ON
				`ktc_combination_course`.`active` = 1  AND
				`ktc_combination_course`.`per_unit` != ".\Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT." AND
				`ktc_combination_course`.`id` = `ts_tcps`.`type_id`	LEFT JOIN
			(
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
				`kolumbus_tuition_blocks` `ktb` INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` INNER JOIN
				`kolumbus_tuition_classes` `ktcl`
			) ON
				`ktbic`.`inquiry_course_id` = `ts_ijc`.`id` AND
				`ktbic`.`program_service_id` = `ts_tcps`.`id` AND
				`ktbic`.`active` = 1 AND
				`ktb`.`id` = `ktbic`.`block_id` AND
				`ktb`.`active` = 1 AND
				`ktbd`.`block_id` = `ktb`.`id` AND
				`ktcl`.`id` = `ktb`.`class_id` AND
				`ktcl`.`active` = 1
		";

		return $additions;

	}

	public function getGroupBy() {
		return ['`tc_c`.`id`', '`ts_ijc`.`id`', '`ktc_combination_course`.`id`'];
	}

	public function getResult($sql, $values) {

		$result = parent::getResult($sql, $values);

		$students = [];

		foreach ($result as $row) {

			$row['lesson_duration'] = (float)$row['lesson_duration'];

			// Ein Schüler soll weiterhin nur einmal gezählt werden, aber der Query holt Rows per Kontakt, Kursbuchung, Teilkurs
			if ($this->checkStudentCourseAllocation($values, $row)) {
				if (!isset($students[$row['contact_id']])) {
					$students[$row['contact_id']] = $row;
				}

				$students[$row['contact_id']]['courses'][] = $row['label_course'];
			}

		}

		// Labels der Teilkurse mergen
		$students = array_map(function ($row) {
			$row['label'] = sprintf('%s (%s)', $row['label'], join(', ', $row['courses']));
			return $row;
		}, $students);

		$result = $this->buildSum($students);

		return $result;

	}

	/**
	 * Prüfen, ob der Schüler (mit dieser Kursbuchung + Teilkurs) gezählt wird oder nicht
	 *
	 * @param FilterValues $values
	 * @param array $row
	 * @return bool
	 */
	private function checkStudentCourseAllocation(FilterValues $values, array $row): bool {

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
		foreach ($lessonsPerDay as $date => $lessons) {

			$date = Carbon::parse($date);

			// In der aktuellen Woche ist mindestens eine Lektion zugewiesen
			if (
				$lessons > 0 &&
				$date->between($values['from'], $values['until'])
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
				$date = \Ext_Thebing_Util::getRealDateFromTuitionWeek(Carbon::parse($week), $day, $row['course_startday']);
				$blockDayLessons[$date->toDateString()] = $lessonsPerDay;
			}
		}

		uksort($blockDayLessons, function ($date1, $date2) {
			return Carbon::parse($date1) > Carbon::parse($date2);
		});

		return $blockDayLessons;

	}

	public function getConfigurationOptions() {
		return [];
	}

}
