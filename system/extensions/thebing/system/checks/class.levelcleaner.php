<?php

class Ext_Thebing_System_Checks_LevelCleaner extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_tuition_progress');

		$sSql = "
			UPDATE
				`kolumbus_tuition_progress` `ktp` INNER JOIN
				`kolumbus_inquiries_courses` `kic` ON
					`kic`.`id` = `ktp`.`inquiry_course_id`
			SET
				`ktp`.`active` = 0
			WHERE
				`ktp`.`active` = 1 AND
				`kic`.`active` = 1 AND
				`ktp`.`week` NOT BETWEEN `kic`.`from` AND `kic`.`until`
		";

		try
		{
			DB::executeQuery($sSql);
		}
		catch(DBx_QueryFailedException $e)
		{
			__pout($e->getMessage());
		}

		return true;
	}

	public function getTitle()
	{
		return 'Levelcleaner';
	}

	public function getDescription()
	{
		return 'Clean tuition progress levels.';
	}
}
