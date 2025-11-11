<?php

class Ext_TS_System_Checks_AccommodationLogin_MovePasswordForgottenTemplate extends GlobalChecks {

	public function getTitle() {
		return 'New place for forgotten password template setting.';
	}

	public function getDescription() {
		return
			'Fills the new place for forgotten password template setting with the value of the first found school which has a value for that setting.';
	}

	public function executeCheck() {

		if (System::d(\TsAccommodationLogin\Handler\ExternalApp::KEY_TEMPLATE) !== null) {
			return true;
		}

		if (!Ext_Thebing_Util::backupTable('wdbasic_attributes')) {
			return false;
		}

		$sql = "
			SELECT
				`value`
			FROM
				`wdbasic_attributes`
			WHERE
				`entity` = 'customer_db_2' AND
			  	`key` = 'accommodationlogin_template' AND
			  	`value` > 0
			LIMIT 1
		";

		$templateId = DB::getQueryOne($sql);
		if ($templateId != null) {
			System::s(\TsAccommodationLogin\Handler\ExternalApp::KEY_TEMPLATE, $templateId);
		}
		DB::executeQuery(
			"DELETE FROM `wdbasic_attributes` WHERE `entity` = 'customer_db_2' AND `key` = 'accommodationlogin_template'"
		);

		return true;
	}

}
