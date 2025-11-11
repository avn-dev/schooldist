<?php

namespace TsStatistic\Generator\Tool\Columns\Tuition;

use Carbon\Carbon;
use Spatie\Period\Period;
use TsStatistic\Generator\Tool\Bases\BookingServicePeriod;
use TsStatistic\Generator\Tool\Columns\AbstractColumn;
use TsStatistic\Generator\Tool\Groupings\Tuition\DefaultTimes;

/**
 * Ticket #15386 – GLS - Teilnehmer und Raumbedarf - Teil 2
 *
 * Raumbedarf (Prognose): Anzahl benötigter Räume (Anzahl Kurse / ⌀ Schüleranzahl des Kurses pro Standardzeit)
 *
 * @TODO Nur mit Gruppierung
 */
class RoomDemand extends AbstractColumn {

	public function getTitle() {
		return self::t('Raumbedarf');
	}

	public function getAvailableBases() {
		return [BookingServicePeriod::class];
	}

	public function getAvailableGroupings() {
		return [DefaultTimes::class];
	}

	public function getSelect() {
		return "
			`tc_c`.`id` `contact_id`,
			`tc_cn`.`number` `label_contact`,
			`ts_ijc`.`id` `journey_course_id`,
			`ts_ijc`.`from` `journey_course_from`,
			`ts_ijc`.`until` `journey_course_until`,
			`ktc_combination_course`.`id` `course_id`,
			GREATEST(`ktc_combination_course`.`average_students`, 1) `average_students`,
			`ktc_combination_course`.`name_short` `label`
		";
	}

	public function getJoinParts() {
		return ['contact_number', 'course'];
	}

	public function getJoinPartsAdditions() {

		$additions = parent::getJoinPartsAdditions();

		$additions['JOIN_JOURNEY_COURSES'] = "
			AND `ts_ijc`.`from` <= :until
			AND `ts_ijc`.`until` >= :from
		";

		$additions['JOIN_COURSES'] = " LEFT JOIN	
			`ts_tuition_courses_programs_services` `ts_tcps` ON
				`ts_tcps`.`program_id` = `ts_ijc`.`program_id` AND
				`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
				`ts_tcps`.`active` = 1 LEFT JOIN
			`kolumbus_tuition_courses` `ktc_combination_course` ON
				`ktc_combination_course`.`active` = 1  AND
				`ktc_combination_course`.`per_unit` != ".\Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT." AND
				`ktc_combination_course`.`id` = `ts_tcps`.`type_id`	
		";

		return $additions;

	}

	public function getGroupBy() {
		return ['`tc_c`.`id`', '`ts_ijc`.`id`', '`ktc_combination_course`.`id`'];
	}

	public function getResult($sql, $values) {

		$periodStatistic = Period::make($values['from'], $values['until']);

		$result = parent::getResult($sql, $values);

		$grouped = [];

		// Jede Kursbuchung (aufgeteilt nach Unterkursen) einzeln durchlaufen, da die Einstellungen der Standardzeiten pro Tag sind
		foreach ($result as $row) {

			$from = Carbon::parse($row['journey_course_from']);
			$until = Carbon::parse($row['journey_course_until']);
			$periodCourse = Period::make($from, $until);
			$periodIntersection = $periodStatistic->overlap($periodCourse);

			if ($periodIntersection === null) {
				throw new \LogicException('No matching period!');
			}

			foreach ($periodIntersection as $date) {
				$date = Carbon::instance($date);
				$tuitionTimeSettings = \Ext_Thebing_Management_Settings_TuitionTime::findByDayAndCourse($date->isoWeekday(), $row['course_id']);
				foreach($tuitionTimeSettings as $tuitionTimeSetting) {
					$grouped[$tuitionTimeSetting->tuition_time_id][$row['course_id']][$row['contact_id']] = $row;
				}
//				if ($tuitionTimeSettings->isEmpty()) {
//					$grouped[0][$row['course_id']][$row['contact_id']] = $row;
//				}
			}

		}

		unset($result);

		$grouped2 = [];

		foreach ($grouped as $tuitionTimeId => $groupedCourse) {

			if (!isset($grouped2[$tuitionTimeId])) {
				$grouped2[$tuitionTimeId] = [
					'result' => 0,
					'grouping_id' => $tuitionTimeId,
					'courses_count' => [],
					'courses_label' => [],
					'courses_average' => [],
					'students' => []
				];
			}

			foreach ($groupedCourse as $courseId => $students) {

				$first = reset($students);
				$avgStudents = (float)$first['average_students'];
				$courseLabel = $first['label'];
				$grouped2[$tuitionTimeId]['result'] += ceil(count($students) / $avgStudents);
				$grouped2[$tuitionTimeId]['courses_count'][$courseId] = count($students);
				$grouped2[$tuitionTimeId]['courses_label'][$courseId] = $courseLabel;
				$grouped2[$tuitionTimeId]['courses_average'][$courseId] = $avgStudents;
				$grouped2[$tuitionTimeId]['students'][$courseId] = array_column($students, 'label_contact');

			}

		}

		// Labels mergen
		$result = collect($grouped2)->map(function ($grouped) {
			$grouped['label'] = array_map(function ($count, $label, $avg, $students) {
				return sprintf('%d × %s (⌀ %d; %s)', $count, $label, $avg, join(', ', $students));
			}, $grouped['courses_count'], $grouped['courses_label'], $grouped['courses_average'], $grouped['students']);
			return $grouped;
		});

		return $result->toArray();

	}

	public function getFormat() {
		return 'number_int';
	}

}
