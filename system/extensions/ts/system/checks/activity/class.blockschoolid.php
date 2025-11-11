<?php

class Ext_TS_System_Checks_Activity_BlockSchoolId extends GlobalChecks {

	public function getTitle() {
		return 'Activity blocks to school check';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		Util::backupTable('ts_activities_blocks');

		$sql = "
			SELECT
				tsactb.*,
				GROUP_CONCAT(DISTINCT ts_acts.school_id) school_ids_activity,
				GROUP_CONCAT(DISTINCT ts_ija.school_id) school_ids_journey
			FROM
				ts_activities_blocks tsactb INNER JOIN
				ts_activities_blocks_to_activities ts_actbta ON
				    ts_actbta.block_id = tsactb.id LEFT JOIN
				(
				    ts_activities ts_act INNER JOIN
				    ts_activities_schools ts_acts
				) ON
					ts_act.id = ts_actbta.activity_id AND
				    ts_act.active = 1 AND
					ts_acts.activity_id = ts_act.id LEFT JOIN
				(
				    ts_activities_blocks_travellers ts_actbt INNER JOIN
				    ts_inquiries_journeys_activities ts_ijact INNER JOIN
				    ts_inquiries_journeys ts_ija
				) ON
				    ts_actbt.block_id = tsactb.id AND
				    ts_ijact.id = ts_actbt.journey_activity_id AND
				    ts_ija.id = ts_ijact.journey_id
			WHERE
			    tsactb.school_id IS NULL OR
			    tsactb.school_id = 0
			GROUP BY
			    tsactb.id
		";

		$blocks = (array)DB::getQueryRows($sql);

		foreach ($blocks as &$block) {
			$schoolIdsActivity = $block['school_ids_activity'] ? explode(',', $block['school_ids_activity']) : [];
			$schoolIdsJourney = $block['school_ids_journey'] ? explode(',', $block['school_ids_journey']) : [];

			if (count($schoolIdsActivity) === 1) {
				$this->logInfo('1: Found activity with one school for block '.$block['id'], $block);
				$block['school_id'] = reset($schoolIdsActivity);
				continue;
			}

			if (count($schoolIdsJourney) === 1) {
				$this->logInfo('2: Found block with allocation of one school for block '.$block['id'], $block);
				$block['school_id'] = reset($schoolIdsJourney);
				continue;
			}

			if (!empty($schoolIdsJourney)) {
				$this->logInfo('3: Use first allocation\'s school for block '.$block['id'], $block);
				$block['school_id'] = reset($schoolIdsJourney);
				continue;
			}

			if (!empty($schoolIdsActivity)) {
				$this->logInfo('4: Use first activity\'s school for block '.$block['id'], $block);
				$block['school_id'] = reset($schoolIdsActivity);
				continue;
			}

			$this->logError('Could not find any school_id for block '.$block['id'], $block);
		}

		unset($block);
		foreach ($blocks as $block) {
			if ($block['school_id']) {
				DB::executePreparedQuery("UPDATE ts_activities_blocks SET school_id = :school_id, changed = changed WHERE id = :id", $block);
			}
		}

		DB::executeQuery("ALTER TABLE `ts_activities_blocks` CHANGE `school_id` `school_id` SMALLINT(5) UNSIGNED NOT NULL");

		DB::executeQuery("ALTER TABLE `ts_activities_blocks` ADD INDEX(`school_id`);");

		return true;

	}

}
