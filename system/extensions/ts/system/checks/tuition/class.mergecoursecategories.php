<?php

include_once Util::getDocumentRoot().'system/includes/admin.inc.php';

class Ext_TS_System_Checks_Tuition_MergeCourseCategories extends GlobalChecks {

	public function getTitle() {
		return 'Migration of course categories';
	}

	public function getDescription() {
		return 'Course categories are going to become a global resource. Please re-assign all course categories you want to become merged like Default to Default. A preselection has already been made.';
	}

	public function executeCheck() {
		global $_VARS;

		$aTables = DB::listTables();
		if(!in_array('kolumbus_tuition_coursecategories', $aTables)) {
			return true;
		}

		// Nochmal ausführen, falls Check direkt ausgeführt wird
		Ext_Thebing_Util::backupTable('kolumbus_tuition_coursecategories');
		Ext_Thebing_Util::backupTable('kolumbus_tuition_courses');
		Ext_Thebing_Util::backupTable('kolumbus_teacher_courses');
		
		$this->deleteOldCourseCategories();
		
		DB::begin(__CLASS__);

		DB::executeQuery("CREATE TABLE `ts_tuition_coursecategories_to_schools` (`category_id` int(11) NOT NULL, `school_id` int(11) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		DB::executeQuery("ALTER TABLE `ts_tuition_coursecategories_to_schools` ADD PRIMARY KEY (`category_id`,`school_id`)");

		DB::executeQuery("INSERT INTO ts_tuition_coursecategories_to_schools SELECT id, school_id FROM kolumbus_tuition_coursecategories WHERE school_id > 0");
	
		foreach((array)$_VARS['coursecategory'] as $iOriginalId => $iTargetId) {

			$this->logInfo('Level '.$iOriginalId.' to '.$iTargetId);

			if($iOriginalId != $iTargetId) {
				$this->migrateCourseCategory($iOriginalId, $iTargetId);
			}

		}
		
		DB::executeQuery("ALTER TABLE `kolumbus_tuition_coursecategories` DROP `idClient`");
		DB::executeQuery("ALTER TABLE `kolumbus_tuition_coursecategories` DROP `school_id`");
		DB::executeQuery("RENAME TABLE `kolumbus_tuition_coursecategories` TO `ts_tuition_coursecategories`");

		DB::commit(__CLASS__);
		
		return true;
	}

	public function printFormContent() {

		// Schon ausgeführt?
		$aTables = DB::listTables();
		if(!in_array('kolumbus_tuition_coursecategories', $aTables)) {
			parent::printFormContent();
			return;
		}

		Ext_Thebing_Util::backupTable('kolumbus_tuition_coursecategories');
		
		$this->deleteOldCourseCategories();
		
		$this->printRows();

		parent::printFormContent();

	}

	private function printRows() {
				
		$sSql = "
			SELECT
				`ktcc`.`id`,
				`ktcc`.`name` `title`,
				CONCAT(`ktcc`.`name`, ' (', `cdb2`.`short`, ')') `name`
			FROM
				`kolumbus_tuition_coursecategories` `ktcc` INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ktcc`.`school_id` AND
					`cdb2`.`active` = 1
			WHERE
				`ktcc`.`active` = 1
			ORDER BY
				`cdb2`.`id`,
				`ktcc`.`name`
		";

		$aResult = (array)DB::getQueryRows($sSql);

		$aLabels = array_combine(array_column($aResult, 'id'), array_column($aResult, 'title'));
		$aCourseCategories = array_combine(array_column($aResult, 'id'), array_column($aResult, 'name'));

		$aMatching = [];
		foreach(array_keys($aCourseCategories) as $iId) {
			foreach($aMatching as $iOriginalId => $iNewId) {
				
				similar_text($aLabels[$iId], $aLabels[$aMatching[$iOriginalId]], $iPercent);
				
				if($iPercent >= 95) {
					$aMatching[$iId] = $iOriginalId;
					break;
				}
			}

			if(!isset($aMatching[$iId])) {
				$aMatching[$iId] = $iId;
			}
		}

		if(count($aCourseCategories) === 1) {
			echo 'Nothing to do!';
			echo '<div style="display: none">';
		}

		printTableStart();
		foreach($aCourseCategories as $iId => $sName) {

			$aOptions = $aCourseCategories;
			foreach($aOptions as $iOptionId => $sLabel) {
				if($iId == $iOptionId) {
					$aOptions[$iOptionId] = 'No change: '.$aOptions[$iOptionId];
				} else {
					$aOptions[$iOptionId] = 'Merge into: '.$aOptions[$iOptionId];
				}
			}

			if($iId) {
				printFormSelect($sName, 'coursecategory['.$iId.']', $aOptions, $aMatching[$iId]);
			}

		}
		
		printTableEnd();

		if(count($aCourseCategories) === 1) {
			echo '</div>';
		}
		
	}
	
	private function deleteOldCourseCategories() {

		$sSql = "
			UPDATE
				`kolumbus_tuition_coursecategories` `ktcc` LEFT JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ktcc`.`school_id` AND
					`cdb2`.`active` = 1
			SET
				`ktcc`.`active` = 0
			WHERE
				`cdb2`.`id` IS NULL OR
				`cdb2`.`active` = 0
		";
		DB::executeQuery($sSql);

	}

	private function migrateCourseCategory($iOriginalId, $iTargetId) {

		// Get Original School Id
		$iOriginalSchoolId = DB::getQueryOne("SELECT school_id FROM kolumbus_tuition_coursecategories WHERE id = :original_id", ['original_id'=>$iOriginalId]);
		
		// Add original school id to target
		try {
			$aData = [
				'category_id' => $iTargetId,
				'school_id' => $iOriginalSchoolId
			];
			DB::insertData('ts_tuition_coursecategories_to_schools', $aData);
		} catch(Exception $e) {
		}
		
		$aSql = [
			'original_id' => $iOriginalId,
			'target_id' => $iTargetId
		];

		$sSql = "
			UPDATE
				kolumbus_tuition_courses
			SET
				category_id = :target_id,
				`changed` = `changed`
			WHERE
				category_id = :original_id
		";
		DB::executePreparedQuery($sSql, $aSql);

		$sSql = "
			UPDATE
				kolumbus_teacher_courses
			SET
				course_id = :target_id
			WHERE
				course_id = :original_id
		";
		DB::executePreparedQuery($sSql, $aSql);

		// Alten Eintrag deaktivieren
		$sSql = "
			UPDATE
				`kolumbus_tuition_coursecategories`
			SET
				`active` = 0
			WHERE
				`id` = :original_id
		";
		DB::executePreparedQuery($sSql, $aSql);

	}

}
