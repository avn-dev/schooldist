<?php

class Ext_Thebing_System_Checks_ActivityTimes extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Generate default activity times';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '8:00 - 17:00';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		$sSql = "SELECT `id` FROM customer_db_2 WHERE active = 1";
		$aSchoolIds = DB::getQueryCol($sSql);

		foreach((array)$aSchoolIds as $iSchoolId) {

			$aData = [
				'created' => date('Y-m-d H:i:s'),
				'active' => 1,
				'school_id' => (int)$iSchoolId,
				'from' => '08:00',
				'until' => '17:00'
			];
			DB::insertData('ts_schools_activities_times', $aData);

		}

		return true;

	}

}
