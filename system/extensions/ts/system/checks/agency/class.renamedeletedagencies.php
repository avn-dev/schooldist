<?php

/**
 * Benent die Namen der gelöschten Agenturen um, damit neu erstellte Agenturen die Namen von den alten nutzen können.
 *
 * https://redmine.thebing.com/issues/14944
 */

class Ext_TS_System_Checks_Agency_RenameDeletedAgencies extends GlobalChecks {

	public function getTitle() {
		return 'Agency Rename Check';
	}

	public function getDescription() {
		return 'Rename old agencies to allow using their names without getting a unique-field error';
	}

	public function executeCheck() {

		Util::backupTable('ts_companies');

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`ka`.`id`
			FROM
				`ts_companies` `ka`
			WHERE
				`ka`.`active` = 0
		";

		$aResults = DB::getQueryRows($sSql);

		$sRegex = '/_.{8}$/';

		foreach($aResults as $aResult) {

			$oAgency = \Ext_Thebing_Agency::getInstance($aResult['id']);

			$bRenamed = (bool)preg_match($sRegex, $oAgency->ext_1);

			if(
				!$bRenamed &&
				$oAgency->active == 0
			) {
				$sRenamedAgency = $oAgency->ext_1.'_'.Ext_TC_Util::generateRandomString(8);

				$aSql = [
					'id' => $oAgency->id,
					'name' => $sRenamedAgency
				];

				$sSql = "
					UPDATE 
						`ts_companies` 
					SET
						`changed` = `changed`,
						`ext_1` = :name
					WHERE 
						`id` = :id
				";

				\DB::executePreparedQuery($sSql, $aSql);

			}

		}

		DB::commit(__CLASS__);

		return true;
	}

}