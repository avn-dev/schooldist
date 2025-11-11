<?php

namespace TsActivities\Entity\Activity;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Period;

class BlockTravellerRepository extends \WDBasic_Repository {

	public function getAllocations(\Ext_TS_Inquiry_Contact_Abstract $student, Carbon $week): array {

		$sql = "
			SELECT
			    `ts_actbt`.`id` `allocation_id`,
			    `ts_actbt`.`week`,
			    `ts_actb`.`id` `block_id`,
			    `ts_actbd`.`day`,
			    `ts_actbd`.`start_time`,
			    `ts_actbd`.`end_time`
			FROM
			    `ts_activities_blocks_travellers` `ts_actbt` INNER JOIN
				`ts_activities_blocks` `ts_actb` ON
				    `ts_actb`.`id` = `ts_actbt`.`block_id` AND
				    `ts_actb`.`active` = 1 INNER JOIN
				`ts_activities_blocks_days` `ts_actbd` ON
				    `ts_actbd`.`block_id` = `ts_actb`.`id` AND
				    `ts_actbd`.`active` = 1
			WHERE
			    `ts_actbt`.`active` = 1 AND
			    `ts_actbt`.`week` = :week AND
			    `ts_actbt`.`traveller_id` = :contact_id
		";

		return (array)\DB::getQueryRows($sql, [
			'week' => $week->toDateString(),
			'contact_id' => $student->id
		]);

	}

	public function checkOverlappingAllocations(\Ext_TS_Inquiry $inquiry, Carbon $week, Period\PeriodCollection $periods): Collection {

		$school = $inquiry->getSchool();

		return collect($this->getAllocations($inquiry->getTraveller(), $week))->filter(function (array $allocation) use ($periods, $school) {
			$start = Carbon::parse($allocation['week'], $school->getTimezone())->addDays($allocation['day'] - 1)->setTimeFromTimeString($allocation['start_time']);
			$end = $start->clone()->setTimeFromTimeString($allocation['end_time']);
			$period = Period\Period::make($start, $end, Period\Precision::MINUTE());

			// Einzeln iterieren, damit man die konkreten Zuweisungen findet
			foreach ($periods as $period2) {
				if ($period2->overlapsWith($period)) {
					return true;
				}
			}

			return false;
		});

	}

	/**
	 * @param int $iBlockId
	 * @param \DateTime $dDate
	 * @return array
	 */
	public function getAllocatedStudents($iBlockId, \DateTime $dDate) {

		$sql = "
			SELECT 
				CONCAT(`tc_c`.`id`, '_', `ts_act`.`id`) `key`,
				`tc_c`.`firstname`, 
				`tc_c`.`lastname`,
				`tc_c`.`id`,
				`ts_act`.`id` `activity_id`,
				`ts_act`.`short` `activity_short`,
				`ts_actbt`.`id` `block_traveller_id`,
				`ts_actbt`.`block_id`,
				`ts_actbt`.`journey_activity_id`,
				`kg`.`name` `group_name`,
				`kg`.`short` `group_short`
			FROM
				`ts_activities_blocks_travellers` `ts_actbt` INNER JOIN 
				`ts_inquiries_journeys_activities` `ts_ijac` ON 
					`ts_actbt`.`journey_activity_id` = `ts_ijac`.`id` INNER JOIN
				`ts_activities` `ts_act` ON
					`ts_ijac`.`activity_id` = `ts_act`.`id` INNER JOIN
				`tc_contacts` `tc_c` ON
				    `tc_c`.`id` = `ts_actbt`.`traveller_id` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
				    `ts_ijac`.`journey_id` = `ts_ij`.`id` INNER JOIN
				`ts_inquiries` `ts_i` ON
				    `ts_ij`.`inquiry_id` = `ts_i`.`id` AND
				    `ts_ij`.`active` = 1 LEFT JOIN
				`kolumbus_groups` `kg` ON
					`kg`.`id` =  `ts_i`.`group_id` AND
					`kg`.`active` = 1
			WHERE
				`ts_actbt`.`block_id` = :block_id AND 
				`ts_actbt`.`week` = :week AND 
			    `ts_actbt`.`active` = 1
		";

		return (array)\DB::getQueryRows($sql, [
			'block_id' => $iBlockId,
			'week' => $dDate->format('Y-m-d')
		]);

	}

	public function getUnallocatedStudents(\Ext_Thebing_School $school, Carbon $from, Carbon $until, string $language, array $filter) {

		$where = "";

		if (
			$filter['booking_state'] === 'unallocated' ||
			$filter['booking_state'] === 'booked'
		) {
			$where .= " AND `ts_ijac`.`id` IS NOT NULL ";
		}

		if (!empty($filter['search'])) {
			$where .= " 
				AND (
					`tc_c`.`firstname` LIKE :search OR
					`tc_c`.`lastname` LIKE :search OR
					`tc_cn`.`number` LIKE :search
				)
			";
		}

		if (!empty($filter['activity'])) {
			$where .= " AND `ts_ijac`.`activity_id` = :activity ";
		}

		if (!empty($filter['inbox'])) {
			$where .= " AND `ts_i`.`inbox` = :inbox ";
		}

		if (!empty($filter['student_status'])) {
			$where .= " AND `ts_i`.`status_id` = :student_status ";
		}

		$where .= \Ext_Thebing_System::getWhereFilterStudentsByClientConfig('ts_i');

		$sql = "
			SELECT
				`ts_i`.`id` `inquiry_id`,
				`ts_ijac`.`id` `journey_activity_id`,
				`ts_ijac`.`weeks`,
				IF(`ts_act`.`billing_period` = 'payment_per_block', `ts_ijac`.`blocks`, 1) `blocks_count`,
				`ts_ijac`.`comment`,
				`ts_act`.`id` `activity_id`,
				`ts_act`.`short` `activity_short`,
				`ts_act_i18n`.`name` `activity_name`,
				`kg`.`name` `group_name`,
				`kg`.`short` `group_short`,
				`tc_c`.`id` `contact_id`,
				`tc_c`.`firstname`,
				`tc_c`.`lastname`,
				GROUP_CONCAT(DISTINCT CONCAT(`ts_actbt`.`id`, ',', `ts_actbt`.`week`, ',', `ts_actbd`.`day`) SEPARATOR ';') `blocks_allocated`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
				    `ts_ij`.`inquiry_id` = `ts_i`.`id` AND
				    `ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				    `ts_ij`.`active` = 1 AND
				    `ts_ij`.`school_id` = :school_id LEFT JOIN
				`ts_inquiries_journeys_activities` `ts_ijac` ON
					`ts_ijac`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijac`.`visible` = 1 AND
					`ts_ijac`.`active` = 1 AND
					`ts_ijac`.`from` <= :until AND
			    	`ts_ijac`.`until` >= :from LEFT JOIN
				`ts_activities` `ts_act` ON
					`ts_act`.`id` = `ts_ijac`.`activity_id` LEFT JOIN
				`ts_activities_i18n` `ts_act_i18n` ON
					`ts_act_i18n`.`activity_id` = `ts_act`.`id` AND
					`ts_act_i18n`.`language_iso` = :language LEFT JOIN
				(
					`ts_activities_blocks_travellers` `ts_actbt` JOIN
					`ts_activities_blocks` `ts_actb` JOIN
					`ts_activities_blocks_days` `ts_actbd`
				) ON
					`ts_actbt`.`journey_activity_id` = `ts_ijac`.`id` AND
					`ts_actbt`.`active` = 1 AND
					`ts_actb`.`id` = `ts_actbt`.`block_id` AND
					`ts_actb`.`active` = 1 AND
					`ts_actbd`.`block_id` = `ts_actb`.`id` AND
					`ts_actbd`.`active` = 1 LEFT JOIN
				`kolumbus_groups` `kg` ON
					`kg`.`id` =  `ts_i`.`group_id` AND
					`kg`.`active` = 1 INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
					`ts_itc`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_itc`.`contact_id` LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id`
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`type` & ".\Ext_TS_Inquiry::TYPE_BOOKING." AND
				`ts_i`.`confirmed` > 0 AND
			    `ts_i`.`service_from` <= :until AND
			    `ts_i`.`service_until` >= :from
			    $where
			GROUP BY
				`ts_i`.`id`,
				`ts_ijac`.`id`
		";

		$rows = (array)\DB::getQueryRows($sql, array_merge($filter, [
			'school_id' => $school->id,
			'language' => $language,
			'from' => $from->toDateString(),
			'until' => $until->toDateString(),
			'search' => $filter['search'].'%',
		]));

		// Für jeden Schüler mit Aktivität muss ein Eintrag ohne Aktivität generiert werden
		$duplicates = [];
		if (
			empty($filter['booking_state']) ||
			$filter['booking_state'] === 'not_booked'
		) {
			foreach ($rows as $row) {
				if (
					$row['journey_activity_id'] &&
					!in_array($row['contact_id'], $duplicates)
				) {
					$row['journey_activity_id'] = null;
					$row['activity_id'] = null;
					$row['comment'] = null;
					$row['blocks_allocated'] = null;

					$rows[] = $row;
					$duplicates[] = $row['contact_id'];
				}
			}
		}

		$format = new \Ext_Gui2_View_Format_Name();
		foreach ($rows as &$row) {
			$row['name'] = $format->formatByResult($row);
			if (!$row['journey_activity_id']) {
				$row['activity_short'] = \L10N::t('N/A', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH);
				$row['activity_name'] = \L10N::t('Keine Aktivität', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH);
				$row['blocks_count'] = 0;
			}

			$row['blocks_allocated_count'] = 0;
			if ($row['blocks_allocated']) {
				$row['blocks_allocated_count'] = Str::of($row['blocks_allocated'])
					->explode(';')
					->count();
			}
		}

		$rows = array_filter($rows, function (array $row) use ($filter) {
			return match ($filter['booking_state']) {
				'unallocated' => $row['blocks_allocated_count'] < $row['blocks_count'],
				'not_booked' => empty($row['journey_activity_id']),
				default => true
			};
		});

		usort($rows, fn(array $row1, array $row2) => strcmp($row1['name'], $row2['name']));

		return $rows;

	}

}
