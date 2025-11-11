<?php

class Ext_Thebing_System_Checks_Marketing_StudentStatusAllSchools extends GlobalChecks {

	public function getTitle() {
		return 'All schools: Student Status';
	}

	public function getDescription() {
		return 'Make student status available for all schools.';
	}

	public function executeCheck() {

		$aFields = DB::describeTable('kolumbus_student_status', true);
		if(isset($aFields['school_id'])) {

			Util::backupTable('kolumbus_student_status');

			DB::executeQuery("TRUNCATE `kolumbus_student_status_schools`");

			$sSql = "
				INSERT INTO
					`kolumbus_student_status_schools`
				SELECT
					`id` `status_id`,
					`school_id` `school_id`
				FROM
					`kolumbus_student_status`
			";

			DB::executeQuery($sSql);

			DB::executeQuery("ALTER TABLE `kolumbus_student_status` DROP `school_id`");

		}

		return true;

	}

}
