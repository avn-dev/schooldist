<?php

class Checks_CleanConfig extends GlobalChecks {

	public function getTitle() {
		return 'Clean config';
	}

	public function getDescription() {
		return 'Removes double entries and adds unique index.';
	}

	public function executeCheck() {

		$bBackup = Util::backupTable('system_config');

		if($bBackup === false) {
			return false;
		}

		$sSql = "
			SELECT 
				`c_key`,
				`c_value`
			FROM 
				`system_config`
			GROUP BY
				`c_key`
			";
		$aConfigs = DB::getQueryRows($sSql);
		
		DB::executeQuery("TRUNCATE TABLE `system_config`");
		
		foreach($aConfigs as $aConfig) {
			DB::insertData('system_config', $aConfig);
		}

		try {
			$sSqlRemoveIndex = "ALTER TABLE `system_config` DROP INDEX `c_key`";
			DB::executeQuery($sSqlRemoveIndex);
		} catch (Exception $ex) {}

		try {
			$sSqlRemoveIndex2 = "ALTER TABLE `system_config` DROP INDEX `c_key_2`";
			DB::executeQuery($sSqlRemoveIndex2);
		} catch (Exception $ex) {}

		try {
			$sSqlDeleteId = "ALTER TABLE `system_config` DROP `c_id`";
			DB::executeQuery($sSqlDeleteId);
		} catch (Exception $ex) {}

		try {
			$sSqlDeleteDescription = "ALTER TABLE `system_config` DROP `c_description`";
			DB::executeQuery($sSqlDeleteDescription);

		} catch (Exception $ex) {}

		$sSqlUnique = "ALTER TABLE `system_config` ADD UNIQUE KEY `c_key` (`c_key`)";
		DB::executeQuery($sSqlUnique);

		return true;
	}

}