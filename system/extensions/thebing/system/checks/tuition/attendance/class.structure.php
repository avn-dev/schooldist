<?php

class Ext_Thebing_System_Checks_Tuition_Attendance_Structure extends GlobalChecks {

	public function getTitle() {
		return 'Attendance Integrity Check';
	}

	public function getDescription() {
		return 'Check for deleted block allocations and still existing attendance entries.';
	}

	public function executeCheck() {

		if(!Util::backupTable('kolumbus_tuition_attendance')) {
			throw new RuntimeException('Couldn\'t backup table');
		}

		// Changed ist neu, daher auf created setzen
		DB::executeQuery("
			UPDATE
				`kolumbus_tuition_attendance`
			SET
				`changed` = `created`
		");

		// creator_id ist neu, daher auf user_id setzen
		DB::executeQuery("
			UPDATE
				`kolumbus_tuition_attendance`
			SET
				`creator_id` = `user_id`
			WHERE
				`creator_id` = 0
		");

		// Anwesenheits-Einträge löschen, deren Blöcke gelöscht wurden
		DB::executeQuery("
			UPDATE
				`kolumbus_tuition_attendance` `kta` LEFT JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`id` = `kta`.`allocation_id`
			SET
				`kta`.`active` = 0
			WHERE
				`ktbic`.`id` IS NULL OR
				`ktbic`.`active` = 0
		");

		return true;

	}
}