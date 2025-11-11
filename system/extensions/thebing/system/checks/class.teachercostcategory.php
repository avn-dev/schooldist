<?php 

class Ext_Thebing_System_Checks_TeacherCostcategory extends GlobalChecks {

	public function isNeeded(){
		global $user_data;
		
		return true;
		
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_teacher_salary` (
		  `id` int(11) NOT NULL auto_increment,
		  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
		  `active` tinyint(1) NOT NULL default '1',
		  `user_id` int(11) NOT NULL,
		  `teacher_id` int(11) NOT NULL,
		  `costcategory_id` int(11) NOT NULL,
		  `valid_from` date NOT NULL,
		  `valid_until` date NOT NULL,
		  `comment` text NOT NULL,
		  `lessons` decimal(10,2) NOT NULL,
		  `lessons_period` varchar(10) NOT NULL,
		  `salary` decimal(10,2) NOT NULL,
		  `salary_period` varchar(10) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `teacher_id` (`teacher_id`),
		  KEY `user_id` (`user_id`),
		  KEY `valid_from` (`valid_from`,`valid_until`),
		  KEY `costcategory_id` (`costcategory_id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
		DB::executeQuery($sSql);

		Ext_Thebing_Util::backupTable('ts_teachers');
		Ext_Thebing_Util::backupTable('kolumbus_teacher_costcategory_history');
		Ext_Thebing_Util::backupTable('kolumbus_teacher_salary');

		$sSql = "TRUNCATE TABLE `kolumbus_teacher_salary`";
		DB::executeQuery($sSql);

		$sSql = "
				SELECT
					*
				FROM
					ts_teachers
				WHERE
					active = 1 AND
					cost_kategorie_id > 0
				";
		$aTeachers = DB::getQueryRows($sSql);

		foreach((array)$aTeachers as $aTeacher) {
			
			if(
				empty($aTeacher['cost_kategorie_date']) ||
				$aTeacher['cost_kategorie_date'] == '0000-00-00 00:00:00'
			) {
				$aTeacher['cost_kategorie_date'] = $aTeacher['created'];
			}

			if(
				empty($aTeacher['cost_kategorie_date']) ||
				$aTeacher['cost_kategorie_date'] == '0000-00-00 00:00:00'
			) {
				$aTeacher['cost_kategorie_date'] = $aTeacher['changed'];
			}

			$aInsert = array();
			$aInsert['active'] = 1;
			$aInsert['teacher_id'] = $aTeacher['id'];
			$aInsert['costcategory_id'] = $aTeacher['cost_kategorie_id'];
			$aInsert['valid_from'] = $aTeacher['cost_kategorie_date'];
			$aInsert['comment'] = $aTeacher['cost_kategorie_info'];
			DB::insertData('kolumbus_teacher_salary', $aInsert);

		}

		$sSql = "ALTER TABLE `ts_teachers` DROP `cost_kategorie_id`";
		DB::executeQuery($sSql);
		$sSql = "ALTER TABLE `ts_teachers` DROP `cost_kategorie_info`";
		DB::executeQuery($sSql);
		$sSql = "ALTER TABLE `ts_teachers` DROP `cost_kategorie_date`";
		DB::executeQuery($sSql);

		$sSql = "DROP TABLE `kolumbus_teacher_costcategory_history`";
		DB::executeQuery($sSql);

		return true;

	}
	
	
	
}
