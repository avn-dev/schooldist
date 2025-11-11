<?php
/*
 * bereinigt fehlerhafte Buchungen die units bei den Kursen haben obwohl es sich nicht um ein Lektionskurs handelt
 */

class Ext_Thebing_System_Checks_Cleanlectioncourses extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_inquiries_courses');

		$sSql = "UPDATE
					`kolumbus_inquiries_courses` `kic` INNER JOIN
						`customer_db_3` `cdb3` ON
							`cdb3`.`id` = `kic`.`course_id` AND
							`kic`.`active` = 1 AND
							`kic`.`units` > 0 AND
							`cdb3`.`per_unit` = 0
					SET
						`kic`.`units` = 0
				";
		
		DB::executeQuery($sSql);

		return true;
	}

	public function getTitle()
	{
		return 'Clear student data'; 
	}

	public function  getDescription()
	{
		return 'Clear student data';
	}
}
