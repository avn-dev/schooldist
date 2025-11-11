<?php

class Ext_TS_System_Checks_Course_CourseLanguages extends GlobalChecks {
	
	public function getTitle() {
		return 'Multiple course language per course';
	}

	public function getDescription() {
		return 'Change database structure';
	}

	public function executeCheck() {
		
		$tableStructure = \DB::describeTable('kolumbus_tuition_courses');
		
		// Wenn das Feld gelöscht wurde, ist der Check schon durch
		if(!array_key_exists('levelgroup_id', $tableStructure)) {
			return true;
		}
		
		$backupTables = [
			'kolumbus_tuition_levelgroups',
			'kolumbus_tuition_courses',
			'ts_inquiries_journeys_courses'
		];

		foreach($backupTables as $backupTable) {
			$backup = Util::backupTable($backupTable);
			if(!$backup) {
				return false;
			}
		}
		
		$sqlQueries = [
			"RENAME TABLE `kolumbus_tuition_levelgroups` TO `ts_tuition_courselanguages`",
			"CREATE TABLE `ts_tuition_courses_to_courselanguages` ( `course_id` INT UNSIGNED NOT NULL , `courselanguage_id` INT UNSIGNED NOT NULL , UNIQUE (`courselanguage_id`, `course_id`)) ENGINE = InnoDB",
			"INSERT INTO `ts_tuition_courses_to_courselanguages` SELECT `id` `course_id`, `levelgroup_id` `courselanguage_id` FROM `kolumbus_tuition_courses` WHERE `levelgroup_id` != 0",
			"ALTER TABLE `kolumbus_tuition_progress` CHANGE `levelgroup_id` `courselanguage_id` MEDIUMINT(9) NOT NULL",
			"ALTER TABLE `kolumbus_tuition_courses` DROP `levelgroup_id`",
			"ALTER TABLE `ts_inquiries_journeys_courses` ADD `courselanguage_id` INT UNSIGNED NULL DEFAULT NULL AFTER `course_id`, ADD INDEX (`courselanguage_id`)",
			"ALTER TABLE `kolumbus_groups_courses` ADD `courselanguage_id` INT UNSIGNED NOT NULL AFTER `course_id`, ADD INDEX (`courselanguage_id`)"
		];
		
		foreach($sqlQueries as $sqlQuery) {
			
			try {
				\DB::executeQuery($sqlQuery);
			} catch(\Throwable $e) {
			}
			
		}
		
		$cacheKeys = [
			'db_table_description_ts_inquiries_journeys_courses',
			'wdbasic_table_description_ts_inquiries_journeys_courses',
			'db_table_description_kolumbus_tuition_courses',
			'wdbasic_table_description_kolumbus_tuition_courses',
			'db_table_description_kolumbus_tuition_progress',
			'wdbasic_table_description_kolumbus_tuition_progress',
		];
		
		foreach($cacheKeys as $cacheKey) {
			\WDCache::delete($cacheKey);
		}
		
		// Kurssprache in Kurs-Buchungstabelle schreiben
		$courseToCourselanguageMapping = \DB::getQueryPairs("SELECT `course_id`, `courselanguage_id` FROM `ts_tuition_courses_to_courselanguages`");
		foreach($courseToCourselanguageMapping as $courseId=>$courselanguageId) {
			\DB::executePreparedQuery("UPDATE `ts_inquiries_journeys_courses` SET `changed` = `changed`, `courselanguage_id` = :courselanguage_id WHERE `course_id` = :course_id AND `courselanguage_id` IS NULL", ['course_id'=>$courseId, 'courselanguage_id'=>$courselanguageId]);
			\DB::executePreparedQuery("UPDATE `kolumbus_groups_courses` SET `changed` = `changed`, `courselanguage_id` = :courselanguage_id WHERE `course_id` = :course_id AND `courselanguage_id` IS NULL", ['course_id'=>$courseId, 'courselanguage_id'=>$courselanguageId]);
		}
		
		return true;
	}

}
