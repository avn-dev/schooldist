<?php

include_once Util::getDocumentRoot().'system/includes/admin.inc.php';

class Ext_TS_System_Checks_Tuition_MergeLevels extends GlobalChecks {

	public function getTitle() {
		return 'Migration of levels';
	}

	public function getDescription() {
		return 'Levels are going to become a global resource. Please re-assign all levels you want to become merged like Default to Default. A preselection has already been made.';
	}

	public function executeCheck() {
		global $_VARS;

		$aTables = DB::listTables();
		if(!in_array('kolumbus_tuition_levels', $aTables)) {
			return true;
		}
		
		// Nochmal ausführen, falls Check direkt ausgeführt wird
		Ext_Thebing_Util::backupTable('kolumbus_tuition_levels');
		Ext_Thebing_Util::backupTable('ts_inquiries_journeys_courses');
		Ext_Thebing_Util::backupTable('kolumbus_tuition_blocks');
		Ext_Thebing_Util::backupTable('kolumbus_tuition_progress');
		$this->deleteOldLevels();
		
		DB::begin(__CLASS__);

		DB::executeQuery("CREATE TABLE `ts_tuition_levels_to_schools` (`level_id` int(11) NOT NULL, `school_id` int(11) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		DB::executeQuery("ALTER TABLE `ts_tuition_levels_to_schools` ADD PRIMARY KEY (`level_id`,`school_id`)");

		DB::executeQuery("INSERT INTO ts_tuition_levels_to_schools SELECT id, idSchool FROM kolumbus_tuition_levels WHERE idSchool > 0");

		foreach((array)$_VARS['level'] as $iOriginalId => $iTargetId) {

			$this->logInfo('Level '.$iOriginalId.' to '.$iTargetId);

			if($iOriginalId != $iTargetId) {
				$this->migrateLevel($iOriginalId, $iTargetId);
			}

		}

		DB::executeQuery("ALTER TABLE `kolumbus_tuition_levels` DROP `idClient`");
		DB::executeQuery("ALTER TABLE `kolumbus_tuition_levels` DROP `idSchool`");
		DB::executeQuery("RENAME TABLE `kolumbus_tuition_levels` TO `ts_tuition_levels`");

		DB::commit(__CLASS__);
		
		return true;
	}

	public function printFormContent() {

		// Schon ausgeführt?
		$aTables = DB::listTables();
		if(!in_array('kolumbus_tuition_levels', $aTables)) {
			parent::printFormContent();
			return;
		}

		Ext_Thebing_Util::backupTable('kolumbus_tuition_levels');
		$this->deleteOldLevels();
		
		$this->printRows('normal');
		$this->printRows('internal');

		parent::printFormContent();

	}

	private function printRows($sType) {
				
		$oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		
		$sLanguage = $oSchool->getLanguage();
		
		$sSql = "
			SELECT
				`ktl`.`id`,
				`ktl`.#field `title`,
				CONCAT(`ktl`.#field, ' (', `cdb2`.`short`, ')') `name`
			FROM
				`kolumbus_tuition_levels` `ktl` INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ktl`.`idSchool` AND
					`cdb2`.`active` = 1
			WHERE
				`ktl`.`active` = 1 AND
				`ktl`.`type` = :type
			ORDER BY
				`cdb2`.`id`,
				`ktl`.`position`
		";

		$aResult = (array)DB::getQueryRows($sSql, ['field'=>'name_'.$sLanguage, 'type'=>$sType]);

		$aLabels = array_combine(array_column($aResult, 'id'), array_column($aResult, 'title'));
		$aLevels = array_combine(array_column($aResult, 'id'), array_column($aResult, 'name'));

		$aMatching = [];
		foreach(array_keys($aLevels) as $iId) {
			foreach($aMatching as $iOriginalId => $iNewId) {

				if($aLabels[$iId] == $aLabels[$aMatching[$iOriginalId]]) {
					$aMatching[$iId] = $iOriginalId;
					break;
				}
			}

			if(!isset($aMatching[$iId])) {
				$aMatching[$iId] = $iId;
			}
		}

		if(count($aLevels) === 1) {
			echo 'Nothing to do!';
			echo '<div style="display: none">';
		}

		printTableStart();
		foreach($aLevels as $iId => $sName) {

			$aOptions = $aLevels;
			foreach($aOptions as $iOptionId => $sLabel) {
				if($iId == $iOptionId) {
					$aOptions[$iOptionId] = 'No change: '.$aOptions[$iOptionId];
				} else {
					$aOptions[$iOptionId] = 'Merge into: '.$aOptions[$iOptionId];
				}
			}

			if($iId) {
				printFormSelect($sName, 'level['.$iId.']', $aOptions, $aMatching[$iId]);
			}

		}
		
		printTableEnd();

		if(count($aLevels) === 1) {
			echo '</div>';
		}
		
	}
	
	private function deleteOldLevels() {

		$sSql = "
			UPDATE
				`kolumbus_tuition_levels` `ktl` LEFT JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ktl`.`idSchool` AND
					`cdb2`.`active` = 1
			SET
				`ktl`.`active` = 0
			WHERE
				`cdb2`.`id` IS NULL OR
				`cdb2`.`active` = 0
		";
		DB::executeQuery($sSql);

	}

	private function migrateLevel($iOriginalId, $iTargetId) {

		// Get Original School Id
		$iOriginalSchoolId = DB::getQueryOne("SELECT idSchool FROM kolumbus_tuition_levels WHERE id = :original_id", ['original_id'=>$iOriginalId]);
		
		// Add original school id to target
		try {
			$aData = [
				'level_id' => $iTargetId,
				'school_id' => $iOriginalSchoolId
			];
			DB::insertData('ts_tuition_levels_to_schools', $aData);
		} catch(Exception $e) {
		}
		
		$aSql = [
			'original_id' => $iOriginalId,
			'target_id' => $iTargetId
		];

		$aTables = [
			'ts_inquiries_journeys_courses' => 'level_id',
			'kolumbus_tuition_blocks' => 'level_id',
			'kolumbus_tuition_progress' => 'level',
			'ts_placementtests_results' => 'level_id'
		];
		
		// Verweise anpassen
		foreach($aTables as $sTable=>$sField) {
			
			$aSql['table'] = $sTable;
			$aSql['field'] = $sField;
			
			$sSql = "
				UPDATE
					#table
				SET
					#field = :target_id,
					`changed` = `changed`
				WHERE
					#field = :original_id
			";
			DB::executePreparedQuery($sSql, $aSql);
		}

		// Alten Eintrag deaktivieren
		$sSql = "
			UPDATE
				`kolumbus_tuition_levels`
			SET
				`active` = 0
			WHERE
				`id` = :original_id
		";
		DB::executePreparedQuery($sSql, $aSql);

	}

}
