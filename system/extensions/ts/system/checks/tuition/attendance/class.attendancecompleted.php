<?php

class Ext_TS_System_Checks_Tuition_Attendance_AttendanceCompleted extends GlobalChecks
{
	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return 'Attendance completed check';
	}

	/**
	 * Gets attendance rows where column "completed" is NULL, calculates value depending on attendance entries, and sets "completed" 0 or 1
	 * @return string
	 */
	public function getDescription(): string
	{
		return 'Checks attendance entries and sets status completed if necessary';
	}

	/**
	 * @return bool
	 */
	public function executeCheck(): bool
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		/*
		 * Backup table.
		 */
		$bSuccess = Util::backupTable('kolumbus_tuition_attendance');

		if (!$bSuccess) {
			__pout('couldnt backup table!');
			return false;
		}

		/*
		 * First get attendance rows with completed null, calculate edited days, set 1 if edited days >= all possible days.
		 */
		DB::begin(__CLASS__);

		$sql = "UPDATE `kolumbus_tuition_attendance` SET `completed` = 1, `changed` = `changed` WHERE `kolumbus_tuition_attendance`.`id` IN (
					SELECT `attendance_id` FROM (
						SELECT
							`kta`.`id` `attendance_id`,
							((`kta`.`mo` IS NOT NULL ) + (`kta`.`di` IS NOT NULL ) + (`kta`.`mi` IS NOT NULL ) + (`kta`.`do` IS NOT NULL ) + (`kta`.`fr` IS NOT NULL ) + (`kta`.`sa` IS NOT NULL ) + (`kta`.`so` IS NOT NULL )) `daysnotnull`,
							COUNT(`ktd`.`day`) as `days`
						FROM
							`kolumbus_tuition_attendance` `kta` INNER JOIN
							`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
								`ktbic`.`id` = `kta`.`allocation_id` AND
								`ktbic`.`active` = 1 INNER JOIN
							`kolumbus_tuition_blocks` `ktb` ON
								`ktb`.`id` = `ktbic`.`block_id` AND
								`ktb`.`week` = `kta`.`week` AND
								`ktb`.`teacher_id` = `kta`.`teacher_id` AND
								`ktb`.`active` = 1 INNER JOIN
							`kolumbus_tuition_blocks_days` `ktd` ON
								`ktd`.`block_id` = `ktb`.`id`
						WHERE
							`kta`.`active` = 1 AND
							`kta`.`completed` IS NULL
						GROUP BY `attendance_id`
					) `temp`
					WHERE `days` <= `daysnotnull`
				);
		";

		try {
			DB::executeQuery($sql);
			DB::executeQuery("UPDATE `kolumbus_tuition_attendance` SET `completed` = 0, `changed` = `changed` WHERE `completed` IS NULL;");
		} catch (\Throwable $e) {
			DB::rollback(__CLASS__);
			__pout($e);
			return false;
		}

		DB::commit(__CLASS__);

		return true;
	}

}