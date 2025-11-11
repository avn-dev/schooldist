<?php

namespace TsStatistic\Generator\Tool\Columns\Tuition;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Period\Period;
use Spatie\Period\Precision;
use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Tool\Bases\BookingServicePeriod;
use TsStatistic\Generator\Tool\Columns\AbstractColumn;
use TsStatistic\Generator\Tool\Groupings\Tuition\DefaultTimes;

/**
 * Ticket #15386 – GLS - Teilnehmer und Raumbedarf - Teil 2
 *
 * TODO Nur mit Gruppierung, nur pro Woche(?)
 *
 * @property DefaultTimes $grouping
 */
class TeacherAvailable extends AbstractColumn {

	/**
	 * @var Period
	 */
	private $period;

	/**
	 * @var Collection
	 */
	private $teachers;

//	/**
//	 * @var Collection
//	 */
//	private $allocations;

	public function getTitle() {
		return self::t('Lehrer verfügbar');
	}

	public function getAvailableBases() {
		return [BookingServicePeriod::class];
	}

	public function getAvailableGroupings() {
		return [DefaultTimes::class];
	}

	public function getSelect() {
		return "0";
	}

	public function getResult(string $sql, FilterValues $values) {

		$labels = $this->grouping->getAllLabels();

		$this->period = Period::make($values->from, $values->until);
		$this->teachers = $this->getTeachers($values);

		$tuitionTimes = collect($labels)->except(0)->keys()->map(function ($id) {
			return \Ext_Thebing_Tuition_Template::getInstance($id);
		});

		$values['week'] = Carbon::instance(\Ext_Thebing_Util::getWeekFromCourseStartDate($values->from));
		$values['teacher_ids'] = $this->teachers->pluck('teacher_id');
		$values['tuition_time_ids'] = $tuitionTimes->pluck('id');

//		$this->allocations = $this->getAllocations($values);

		$grouped = $tuitionTimes->map(function (\Ext_Thebing_Tuition_Template $tuitionTemplate) {
			return $this->getTuitionTimeResult($tuitionTemplate);
		});

		return $grouped->toArray();

	}

	private function getTeachers(FilterValues $values) {

		$sql = "
			SELECT
				`ts_t`.`id` `teacher_id`,
			    CONCAT(`ts_t`.`lastname`, ', ', `ts_t`.`firstname`) `name`,
			    GROUP_CONCAT(DISTINCT CONCAT(`kts`.`idDay`, ',', `kts`.`timeFrom`, ',', `kts`.`timeTo`) SEPARATOR ';') `schedule`,
				GROUP_CONCAT(DISTINCT CONCAT(`ka`.`from`, ',', `ka`.`until`) SEPARATOR ';') `absence`
			FROM
				`ts_teachers` `ts_t` INNER JOIN
				`ts_teachers_to_schools` `ts_ts` ON
				    `ts_ts`.`teacher_id` = `ts_t`.`id` AND
				    `ts_ts`.`school_id` IN (:schools) INNER JOIN
				`kolumbus_teacher_schedule` `kts` ON
					`kts`.`idTeacher` = `ts_t`.`id` AND
					`kts`.`active` = 1 LEFT JOIN
				`kolumbus_absence` `ka` ON
					`ka`.`item` = 'teacher' AND
					`ka`.`id` = `ts_t`.`id` AND
					`ka`.`active` = 1 AND
					`ka`.`from` >= :from AND
					`ka`.`until` <= :until
			WHERE
				`ts_t`.`active` = 1 AND (
			    	`ts_t`.`valid_until` = '0000-00-00' OR
			    	`ts_t`.`valid_until` >= :until
				)
			GROUP BY
				`ts_t`.`id`
		";

		return collect(\DB::getQueryRows($sql, $values->toSqlData()))->map(function (array $row) {
			$row['schedule'] = collect(explode(';', $row['schedule']))->reduce(function (array $schedule, string $rawSchedule) {
				[$day, $from, $until] = explode(',', $rawSchedule);
				$schedule[$day][] = [$from, $until];
				return $schedule;
			}, []);
			return $row;
		});

	}

//	private function getAllocations(FilterValues $values) {
//
//		$sql = "
//			SELECT
//				`ktt`.`id`,
//			    GROUP_CONCAT(`ktb`.`teacher_id`) `teacher_ids`
//			FROM
//				`kolumbus_tuition_templates` `ktt` INNER JOIN
//				`kolumbus_tuition_blocks` `ktb` ON
//					`ktb`.`week` = :week AND
//					`ktb`.`teacher_id` IN (:teacher_ids) AND
//					`ktb`.`active` = 1 INNER JOIN
//				`kolumbus_tuition_templates` `ktt2` ON
//					`ktt2`.`id` = `ktb`.`template_id` AND
//					`ktt2`.`from` >= `ktt`.`from` AND
//					`ktt2`.`until` <= `ktt`.`until`
//			WHERE
//				`ktt`.`id` IN (:tuition_time_ids) AND
//			    `ktt`.`active` = 1
//			GROUP BY
//				`ktt`.`id`
//		";
//
//		return collect(\DB::getQueryRowsAssoc($sql, $values->toSqlData()))->map(function (array $row) {
//			$row['teacher_ids'] = explode(',', $row['teacher_ids']);
//			return $row;
//		});
//
//	}

	private function getTuitionTimeResult(\Ext_Thebing_Tuition_Template $tuitionTemplate) {

		$teachersAbsence = collect();
		$teachersTimes = collect();
//		$teachersAllocated = collect();

		$teachersAvailable = $this->teachers->filter(function (array $row) use ($tuitionTemplate, $teachersAbsence, $teachersTimes) {

			// Standardzeiten haben nichts mit Tagen zu tun und wg. wöchentlicher Ansicht ist die ganze Woche dann gesperrt
			if (!empty($row['absence'])) {
				$teachersAbsence->push($row);
				return false;
			}

			$tuitionTimeSetting = \Ext_Thebing_Management_Settings_TuitionTime::findOneByTuitionTime($tuitionTemplate->id);
			if ($tuitionTimeSetting === null) {
				$teachersTimes->push($row);
				return false;
			}

			$schedule = $row['schedule'];
			$dayAvailability = array_fill_keys($tuitionTimeSetting->days, false);

			foreach ($this->period as $date) {
				$date = Carbon::instance($date);

				// TuitionTime wird an diesem Tag nicht verwendet
				if (!in_array($date->isoWeekday(), $tuitionTimeSetting->days)) {
					continue;
				}

				// Lehrer unterrichtet an dem Tag nicht
				if (!isset($schedule[$date->isoWeekday()])) {
					continue;
				}

				$date1 = $date->copy()->setTimeFromTimeString($tuitionTemplate->from);
				$date2 = $date->copy()->setTimeFromTimeString($tuitionTemplate->until);
				$period = Period::make($date1, $date2, Precision::MINUTE());

				// Verfügbare Zeiten des Lehrers pro Tag vergleichen
				$anyOverlap = collect($schedule[$date->isoWeekday()])->some(function ($time) use($date, $period) {

					$date1 = $date->copy()->setTimeFromTimeString($time[0]);
					$date2 = $date->copy()->setTimeFromTimeString($time[1]);
					$period2 = Period::make($date1, $date2, Precision::MINUTE());

					return $period->overlap($period2) !== null;

				});

				if (!$anyOverlap) {
					continue;
				}

				$dayAvailability[$date->isoWeekday()] = true;

			}

			// Lehrer muss an jedem Tag verfügbar sein, an welchem die TuitionTime zugewiesen ist
			$allDaysAvailable = collect($dayAvailability)->every(function ($dayValue) {
				return $dayValue;
			});

			if (!$allDaysAvailable) {
				$teachersTimes->push($row);
				return false;
			}

			// Prüfen, ob Lehrer zu dieser Zeit zugewiesen ist
//			if (
//				$this->allocations->has($tuitionTemplate->id) &&
//				array_search($row['id'], $this->allocations->get($tuitionTemplate->id)['teacher_ids']) !== false
//			) {
//				$teachersAllocated->push($row);
//				return false;
//			}

			return true;

		});

		return [
			'result' => $teachersAvailable->count(),
			'label' => [vsprintf("%s: %s\n%s: %s\n%s: %s", [
				self::t('Verfügbar'),
				$this->formatTeacherLabel($teachersAvailable),
//				self::t('Zugewiesen'),
//				$this->formatTeacherLabel($teachersAllocated),
				self::t('Abwesend'),
				$this->formatTeacherLabel($teachersAbsence),
				self::t('Nicht verfügbar'),
				$this->formatTeacherLabel($teachersTimes),
			])],
			'grouping_id' => $tuitionTemplate->id,
			'grouping_label' => $tuitionTemplate->getNameAndTime()
		];


	}

	private function formatTeacherLabel(Collection $collection) {

		if ($collection->isEmpty()) {
			return '–';
		}

		return $collection->map(function (array $row) {
			return $row['name'];
		})->join('; ');

	}

}