<?php

class Ext_Thebing_System_Checks_User_EmailSettings extends GlobalChecks {

	public function getTitle() {
		return 'Update user e-mail settings';
	}

	public function getDescription() {
		return 'User e-mail settings per school';
	}

	public function executeCheck() {

		if(DB::getDefaultConnection()->checkField('kolumbus_user_identities', 'school_id', true)) {
			return true;
		}

		Util::backupTable('system_user');
		Util::backupTable('kolumbus_user_identities');

		DB::executeQuery(" ALTER TABLE kolumbus_user_identities DROP PRIMARY KEY ");
		DB::addField('kolumbus_user_identities', 'school_id', 'SMALLINT UNSIGNED NOT NULL', 'user_id');
		DB::executeQuery(" ALTER TABLE `kolumbus_user_identities` ADD PRIMARY KEY( `user_id`, `school_id`, `identity_id`); ");

		DB::begin(__CLASS__);

		$aSchoolIds = array_keys(Ext_Thebing_Client::getSchoolList(true));
		array_unshift($aSchoolIds, 0);

		$sSql = "
			SELECT
				`su`.`id`,
				`su`.`send_from_this_email`,
				`su`.`thebing_email_account`
			FROM
				`system_user` `su`
			WHERE
				`su`.`active` = 1
			GROUP BY
				`su`.`id`
		";

		$aResult = (array)DB::getQueryRows($sSql);

		foreach($aResult as $aUser) {

			foreach($aSchoolIds as $iSchoolId) {
				DB::insertData('ts_system_user_schoolsettings', [
					'user_id' => $aUser['id'],
					'school_id' => $iSchoolId,
					'use_setting' => (int)$aUser['send_from_this_email'],
					'emailaccount_id' => (int)$aUser['thebing_email_account']
				]);
			}

		}

		$sSql = "
			SELECT
				`user_id`,
				`identity_id`
			FROM
				`kolumbus_user_identities`
		";

		$aIdentities = (array)DB::getQueryRows($sSql);

		foreach($aIdentities as $aRow) {
			foreach($aSchoolIds as $iSchoolId) {
				if($iSchoolId == 0) {
					// Da Spalte hinzugefügt wurde, gibt es schon Einträge mit 0
					continue;
				}

				$aRow['school_id'] = $iSchoolId;
				DB::insertData('kolumbus_user_identities', $aRow);
			}
		}

		DB::commit(__CLASS__);

		DB::executeQuery(" ALTER TABLE `kolumbus_user_identities` ORDER BY `user_id`, `school_id` ");
		DB::executeQuery(" ALTER TABLE `system_user` DROP `send_from_this_email`, DROP `thebing_email_account` ");

		return true;

	}

}
