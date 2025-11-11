<?php


class Ext_Thebing_System_Checks_ClearClassCourses extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_tuition_classes_courses');
		Ext_Thebing_Util::backupTable('kolumbus_tuition_classes');

		$sSql = "
			DELETE
				`ktclc`
			FROM
				`kolumbus_tuition_classes_courses` `ktclc` INNER JOIN
				`kolumbus_tuition_classes` `ktcl` ON
					`ktcl`.`id` = `ktclc`.`class_id`
			WHERE
				`ktcl`.`active` = 0
		";

		try
		{
			DB::executeQuery($sSql);
		}
		catch(DB_QueryFailedException $e)
		{
			
		}

		return true;
	}

	public function getTitle()
	{
		return 'Check Courses';
	}

	public function getDescription()
	{
		return 'Check courses for inactive classes.';
	}

}