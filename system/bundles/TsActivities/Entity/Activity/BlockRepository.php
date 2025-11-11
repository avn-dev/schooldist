<?php

namespace TsActivities\Entity\Activity;

use \TsActivities\Entity\Activity;

class BlockRepository extends \WDBasic_Repository {

	/**
     * @todo - Methode liefert auch Daten über $sEnd hinaus
	 */
	public function getBlocksForTimeframe(\DateTimeInterface $start, \DateTimeInterface $end, \Ext_Thebing_School $school) {

		$sql = "
			SELECT 
				`ts_actb`.`id`, 
				`ts_actb`.`name`, 
				COUNT(DISTINCT`ts_abt`.`id`) `student_count`,
				`ts_actb`.`weeks`, 
				`ts_actb`.`repeat_weeks`, 
				`ts_actb`.`frontend_release`,
				`ts_actb`.`start_week`,
				`ts_actbd`.`start_time`, 
				`ts_actbd`.`end_time`, 
				`ts_actbd`.`day`,
				`ts_actbd`.`place`,
				GROUP_CONCAT(DISTINCT `su`.`firstname`, ' ', `su`.`lastname` SEPARATOR ', ') `activity_leaders`,
				GROUP_CONCAT(DISTINCT `ts_act`.`id`) `activity_ids`
			FROM 
				`ts_activities_blocks` `ts_actb` INNER JOIN
				`ts_activities_blocks_days` `ts_actbd` ON
					`ts_actb`.`id` = `ts_actbd`.`block_id` AND 
					`ts_actbd`.`active` = 1 INNER JOIN
				`ts_activities_blocks_to_activities` `ts_abta` ON
				    `ts_abta`.`block_id` = `ts_actb`.`id` INNER JOIN
				`ts_activities` `ts_act` ON
				    `ts_act`.`id` = `ts_abta`.`activity_id` AND
				    `ts_act`.`active` = 1 LEFT JOIN
				`ts_activities_blocks_travellers` `ts_abt` ON
				    `ts_abt`.`block_id` = `ts_actb`.`id` AND
				    `ts_abt`.`week` = :start AND
				    `ts_abt`.`active` = 1 LEFT JOIN
				`ts_activities_blocks_days_accompanying_persons` `ts_abdap` ON
				    `ts_abdap`.`day_id` = `ts_actbd`.`id` LEFT JOIN
				`system_user` `su` ON
				    `su`.`id` = `ts_abdap`.`user_id`
			WHERE
				`ts_actb`.`active` = 1 AND
				`ts_actb`.`school_id` = :school_id AND
				`ts_actb`.`start_week` <= :end AND
				DATE_ADD(`ts_actb`.`start_week`, INTERVAL `ts_actb`.`weeks` WEEK) >= :start
			GROUP BY
			    `ts_actb`.`id`,
			    `ts_actbd`.`id`
		";

		return (array)\DB::getQueryRows($sql, [
			'start'=> $start->format('Y-m-d'),
			'end' => $end->format('Y-m-d'),
			'school_id' => $school->id
		]);

	}

	/**
	 * @return array
	 */
	public function getCompanionsForSelect() {

		$aResult = \Ext_Thebing_User::getArrayByFunction('activity_guide');

		return $aResult;
	}

	/**
	 *
	 */
	public function getTravellersForExport(Activity\Block $block, \DateTime $week): array {

		$sql = "
			SELECT
				`traveller_id`
			FROM
				`ts_activities_blocks_travellers`
			WHERE
				`block_id` = :block_id AND
				`week` = :week_date AND
				`active` = 1
		";

		return (array)\DB::getQueryCol($sql, [
			"block_id" => $block->id,
			"week_date" => $week->format('Y-m-d')
		]);

	}
}
