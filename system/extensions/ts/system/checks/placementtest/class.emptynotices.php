<?php

class Ext_TS_System_Checks_Placementtest_EmptyNotices extends GlobalChecks {

	public function getTitle() {
		return 'Placementtest: Empty notices';
	}

	public function getDescription() {
		return 'Deleting all empty entries from the placementtest notices table';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '2G');

		$backup = Ext_Thebing_Util::backupTable('ts_placementtests_results_details_notices');
		if (!$backup) {
			return false;
		}

		DB::executeQuery("DELETE FROM `ts_placementtests_results_details_notices` WHERE `comment` IS NULL OR `comment` = ''");

		return true;
	}

}
