<?php

class Ext_Thebing_System_Checks_Statistic_FixDefaultStatistics extends GlobalChecks {

	public function getTitle() {
		return 'Fix default statistics';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		if(
			Ext_Thebing_Util::isDevSystem() ||
			Ext_Thebing_Util::isTestSystem()
		) {
			return true;
		} elseif(Ext_Thebing_Util::isLive2System()) {
			$oUpdate = new Ext_Thebing_Update('test');
		} else {
			$oUpdate = new Ext_Thebing_Update('live');
		}

		$aNames = [
			'Default_Classes_Forecast (students in school - course related only)',
			'Default_Marketing_Agent performance (registration date)',
			'Default_Marketing_Nationality overview (registration date)',
			'Default_Marketing_Top 10 Agents',
			'Default_Sales_Bookings per month (registration date)',
			'Default_Student_weeks_per_course (invoice, service time)',
			'Default_Students_at_school_per_course_category'
		];

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_statistic_statistics`
			WHERE
				`title` IN ( :names )
		";

		$aIds = (array)DB::getQueryCol($sSql, ['names' => $aNames]);

		foreach($aIds as $iId) {
			DB::executeQuery("DELETE FROM `kolumbus_statistic_statistics` WHERE `id` = $iId");
			DB::executeQuery("DELETE FROM `kolumbus_statistic_cols` WHERE `statistic_id` = $iId");
			DB::executeQuery("DELETE FROM `kolumbus_statistic_statistic_intervals` WHERE `statistic_id` = $iId");
		}

		$oUpdate->updateBasicStatistics();

		DB::commit(__CLASS__);

		return true;

	}

}