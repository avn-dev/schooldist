<?php

class Ext_TS_System_Checks_Flex_Uploads extends GlobalChecks {

	public function getTitle() {
		return 'Make custom upload fields multi-school';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		$aTables = DB::listTables();
		if(in_array('kolumbus_school_customerupload', $aTables)) {
			DB::executeQuery(" RENAME TABLE `kolumbus_school_customerupload` TO `ts_flex_uploads` ");
		}

		if(!in_array('ts_flex_uploads_schools', $aTables)) {
			DB::executeQuery("
				CREATE TABLE `ts_flex_uploads_schools` (
			  		`upload_id` smallint(5) UNSIGNED NOT NULL,
			  		`school_id` smallint(5) UNSIGNED NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			");

			DB::executeQuery("
				ALTER TABLE `ts_flex_uploads_schools`
			  		ADD PRIMARY KEY (`upload_id`,`school_id`);
			");
		}

		$aDescribe = DB::describeTable('ts_flex_uploads', true);
		if(isset($aDescribe['school_id'])) {

			Util::backupTable('kolumbus_school_customerupload');

			DB::executeQuery("
				INSERT INTO
					`ts_flex_uploads_schools`
				SELECT
					`id` `upload_id`,
					`school_id`
				FROM
					`ts_flex_uploads`
			");

			DB::executeQuery(" ALTER TABLE `ts_flex_uploads` DROP `school_id` ");

		}

		return true;

	}

}
