<?php

class Ext_Thebing_System_Checks_Agencygroups extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Import AgencyGroups';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		try{
			Ext_Thebing_Util::backupTable('kolumbus_agencies');
		}catch(Exception $e){

		}

		$sSql = "SELECT
						*
					FROM
						`kolumbus_agencies`
					WHERE
						`active` = 1
				";

		$aAgencies = DB::getQueryData($sSql);

		foreach((array)$aAgencies as $aData){

			$aGroups = explode(',', $aData['ext_30']);

			foreach($aGroups as $iKey => $sValue){
				$iGroupId = (int)trim($sValue);
				if($iGroupId > 0){ 

					$sSql = "INSERT INTO
								`kolumbus_agency_groups_assignments`
							SET
								`agency_id` = :agency_id,
								`group_id` = :group_id
					";
					$aSql = array();
					$aSql['agency_id'] = (int)$aData['id'];
					$aSql['group_id'] = (int)$iGroupId;

					DB::executePreparedQuery($sSql, $aSql);
				}
			}

		}

		$sSql = "ALTER TABLE `kolumbus_agencies` DROP `ext_30`";
		DB::executeQuery($sSql);

		return true;

	}

}
