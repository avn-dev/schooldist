<?php

class Ext_Thebing_System_Checks_Tuition_MergeLevelGroups extends GlobalChecks {

	public function getTitle() {
		return 'Migration of level groups to course languages';
	}

	public function getDescription() {
		return 'Level groups are going to become a global resource. Please re-assign all level groups you want to become merged like Default to Default. A preselection has already been made.';
	}

	public function executeCheck() {
		global $_VARS;

		if(empty($_VARS['levelgroup'])) {
			echo 'No levelgroup data!';
			return false;
		}

		Util::backupTable('kolumbus_tuition_levelgroups');
		Util::backupTable('kolumbus_tuition_courses');
		Util::backupTable('kolumbus_tuition_progress');

		DB::begin(__CLASS__);

		// Levelgruppen gelöschter Schulen löschen
		$this->deleteOldLevelGroups();
		$this->sortLevelGroups();

		foreach($_VARS['levelgroup'] as $iOriginalId => $iTargetId) {

			$this->logInfo('Levelgroup '.$iOriginalId.' to '.$iTargetId);

			if($iOriginalId != $iTargetId) {
				$this->migrateLevelGroup($iOriginalId, $iTargetId);
			}

		}

		DB::commit(__CLASS__);

		return true;

	}

	public function printFormContent() {

		$sSql = "
			SELECT
				`ktlg`.`id`,
				`ktlg`.`title`,
				CONCAT(`ktlg`.`title`, ' (', `cdb2`.`short`, ')') `name`
			FROM
				`kolumbus_tuition_levelgroups` `ktlg` INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ktlg`.`school_id` AND
					`cdb2`.`active` = 1
			WHERE
				`ktlg`.`active` = 1
		";

		$aResult = (array)DB::getQueryRows($sSql);

		$aLabels = array_combine(array_column($aResult, 'id'), array_column($aResult, 'title'));
		$aLevelgroups = array_combine(array_column($aResult, 'id'), array_column($aResult, 'name'));

		$aMatching = [];
		foreach(array_keys($aLevelgroups) as $iId) {
			foreach($aMatching as $iOriginalId => $iNewId) {
				similar_text($aLabels[$iId], $aLabels[$aMatching[$iOriginalId]], $iPercent);
				if($iPercent >= 80) {
					$aMatching[$iId] = $iOriginalId;
					break;
				}
			}

			if(!isset($aMatching[$iId])) {
				$aMatching[$iId] = $iId;
			}
		}

		if(count($aLevelgroups) === 1) {
			echo 'Nothing to do!';
			echo '<div style="display: none">';
		}

		printTableStart();
		foreach($aLevelgroups as $iId => $sName) {

			$aOptions = $aLevelgroups;
			foreach($aOptions as $iOptionId => $sLabel) {
				if($iId == $iOptionId) {
					$aOptions[$iOptionId] = 'No change: '.$aOptions[$iOptionId];
				} else {
					$aOptions[$iOptionId] = 'Merge into: '.$aOptions[$iOptionId];
				}
			}

			if($iId) {
				printFormSelect($sName, 'levelgroup['.$iId.']', $aOptions, $aMatching[$iId]);
			}

		}

		printTableEnd();

		if(count($aLevelgroups) === 1) {
			echo '</div>';
		}

		parent::printFormContent();

	}

	private function deleteOldLevelGroups() {

		$sSql = "
			UPDATE
				`kolumbus_tuition_levelgroups` `ktlg` LEFT JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ktlg`.`school_id` AND
					`cdb2`.`active` = 1
			SET
				`ktlg`.`active` = 0
			WHERE
				`cdb2`.`id` IS NULL OR
				`cdb2`.`active` = 0
		";
		DB::executeQuery($sSql);

	}

	private function sortLevelGroups() {

		// Alte Sortierung ging nach title ASC
		$sSql = "
			SELECT
				`id`
			FROM
				`kolumbus_tuition_levelgroups`
			WHERE
				`active` = 1
			ORDER BY
				`title`
		";

		$aResult = (array)DB::getQueryCol($sSql);

		foreach($aResult as $iKey => $iId) {
			DB::updateData('kolumbus_tuition_levelgroups', [
				'position' => $iKey + 1
			], [
				'id' => $iId
			]);
		}

	}

	private function migrateLevelGroup($iOriginalId, $iTargetId) {

		$aSql = [
			'original_id' => $iOriginalId,
			'target_id' => $iTargetId
		];

		$sSql = "
			UPDATE
				`kolumbus_tuition_courses`
			SET
				`levelgroup_id` = :target_id,
			    `changed` = `changed`
			WHERE
				`levelgroup_id` = :original_id
		";
		DB::executePreparedQuery($sSql, $aSql);

		$sSql = "
			UPDATE
				`kolumbus_tuition_progress`
			SET
				`levelgroup_id` = :target_id,
			     `changed` = `changed`
			WHERE
				`levelgroup_id` = :original_id
		";
		DB::executePreparedQuery($sSql, $aSql);

		$sSql = "
			UPDATE
				`kolumbus_tuition_levelgroups`
			SET
				`active` = 0
			WHERE
				`id` = :original_id
		";
		DB::executePreparedQuery($sSql, $aSql);

	}

}
