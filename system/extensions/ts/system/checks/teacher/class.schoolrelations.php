<?php

class Ext_TS_System_Checks_Teacher_SchoolRelations extends GlobalChecks {
	
	public function getTitle() {
		return 'Extending the allocation of teachers to schools';
	}
	
	public function getDescription() {
		return 'Teachers can be assigned to several schools.';
	}
	
	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$aTables = DB::listTables();
		
		if(in_array('kolumbus_teachers', $aTables)) {
			$bBackup = Ext_Thebing_Util::backupTable('kolumbus_teachers');

			if(!$bBackup) {
				__pout('backup error!');
				return false;
			}
		}
		
		DB::begin(__METHOD__);
		
		// Erster Teil
		$sTableName = DB::getQueryOne("SELECT `external_table` FROM `customer_db_config` WHERE `id` = 32");
		if($sTableName !== 'ts_teachers') {
	
			try {

				/*
				 * Lehrer von nicht vorhandenen / inaktiven Schulen löschen, 
				 * es kann sonst sein, dass komplett fremde Lehrer angezeigt werden in der Liste.
				 */
				DB::executePreparedQuery("DELETE FROM `kolumbus_teachers` WHERE `idSchool` NOT IN (:schools)", ['schools'=> array_keys(Ext_Thebing_Client::getSchoolList(true))]);

				$aTeachers = DB::getQueryRows("SELECT * FROM `kolumbus_teachers` WHERE `idSchool` IN (:schools)", ['schools'=> array_keys(Ext_Thebing_Client::getSchoolList(true))]);

				DB::executeQuery("CREATE TABLE `ts_teachers_to_schools` (`teacher_id` int(11) NOT NULL, `school_id` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
				DB::executeQuery("ALTER TABLE `ts_teachers_to_schools` ADD UNIQUE KEY `teacher_id` (`teacher_id`,`school_id`)");

				foreach($aTeachers as $aTeacher) {
					DB::insertData('ts_teachers_to_schools', ['teacher_id'=>$aTeacher['id'], 'school_id' => $aTeacher['idSchool']]);
				}

				DB::executeQuery("RENAME TABLE `kolumbus_teachers` TO `ts_teachers`");
				DB::executeQuery("RENAME TABLE `kolumbus_teachers_payments` TO `ts_teachers_payments`");
				DB::executeQuery("RENAME TABLE `kolumbus_teachers_payments_to_transactions` TO `ts_teachers_payments_to_transactions`");

				DB::executeQuery("ALTER TABLE `ts_teachers` DROP `idClient`");
				DB::executeQuery("ALTER TABLE `ts_teachers` DROP `idSchool`");

				DB::executeQuery("UPDATE `customer_db_config` SET `external_table` = 'ts_teachers' WHERE `customer_db_config`.`id` = 32");

			} catch(Exception $e) {
				__pout($e);
				DB::rollback(__METHOD__);
				return false;
			}			
			
		}
		
		// Zweiter Teil (Resourcen)
		
		$aSchools = Ext_Thebing_Client::getSchoolList(false, null, true);
		
		$sNewTeacherDocumentsDir = Util::getDocumentRoot().'storage/ts/teachers/documents/';
		Util::checkDir($sNewTeacherDocumentsDir);
		
		$sNewTeacherCommentsDir = Util::getDocumentRoot().'storage/ts/teachers/comments/';
		Util::checkDir($sNewTeacherCommentsDir);
		
		foreach($aSchools as $oSchool) {

			$sSchoolDir = $oSchool->getSchoolFileDir(true);

			$sDir = $sSchoolDir.'/teachers/documents/*';
			
			$aFiles = glob($sDir);

			if(is_array($aFiles)) {
				foreach($aFiles as $sFile) {
					$aFile = pathinfo($sFile);
					rename($sFile, $sNewTeacherDocumentsDir.$aFile['basename']);
				}
			}
			
			$sDir = $sSchoolDir.'/teachers/comments/*';
			
			$aFiles = glob($sDir);

			if(is_array($aFiles)) {
				foreach($aFiles as $sFile) {
					$aFile = pathinfo($sFile);
					rename($sFile, $sNewTeacherCommentsDir.$aFile['basename']);
				}
			}

		}

		// Vertragsparameter / Kostenkategorien
		$aTeacherSalaryTable = DB::describeTable('kolumbus_teacher_salary');
		if(!array_key_exists('school_id', $aTeacherSalaryTable)) {

			Ext_Thebing_Util::backupTable('kolumbus_teacher_salary');

			DB::executeQuery("ALTER TABLE `kolumbus_teacher_salary` ADD `school_id` INT NOT NULL AFTER `costcategory_id`");
			DB::executeQuery("UPDATE `kolumbus_teacher_salary` s SET s.changed = s.changed, s.`school_id` = (SELECT t.school_id FROM kolumbus_costs_kategorie_teacher t WHERE t.id = s.costcategory_id LIMIT 1)");
			DB::executeQuery("UPDATE `kolumbus_teacher_salary` s SET s.changed = s.changed, s.`school_id` = (SELECT ts.school_id FROM ts_teachers_to_schools ts WHERE ts.teacher_id = s.teacher_id LIMIT 1) WHERE s.`school_id` = 0");

		}
		
		DB::commit(__METHOD__);		
		
		return true;
	}
	
}