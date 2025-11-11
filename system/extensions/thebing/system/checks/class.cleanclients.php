<?php

class Ext_Thebing_System_Checks_CleanClients extends Ext_Thebing_System_ThebingCheck {

	public function getTitle() {
		$sTitle = 'Clean client data';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Removes unneeded data from database. First you have to set active = 0 for every client on this installation you dont need any longer!';
		return $sDescription;
	}

	public function isNeeded() {
		if(
			Ext_Thebing_Util::isDevSystem() ||
			Ext_Thebing_Util::isTestSystem() ||
			Ext_Thebing_Util::isLive2System()
		) {
			return false;
		}
		return true;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$sSql = "
				SELECT
					`id`
				FROM
					`kolumbus_clients`
				WHERE 
					`active` = 1";
		$aClients = DB::getQueryCol($sSql);

		/**
		 * Clean /media/secure/client directory
		 */
		$sDirectory = \Util::getDocumentRoot().'storage/clients/*';
		$aClientDirectories = glob($sDirectory);
		foreach((array)$aClientDirectories as $sPath) {
			if(is_dir($sPath)) {
				$iClientId = substr($sPath, strrpos($sPath, '_')+1);
				if(!in_array($iClientId, $aClients)) {
					Ext_Thebing_Util::recursiveDelete($sPath);
				}
			}
		}

		/**
		 * Clean tables
		 */
		$aTables = DB::listTables();

		$aClientTables = array();
		foreach((array)$aTables as $sTable) {

			if(strpos($sTable, '__') === 0) {
				continue;
			}

			$aDescribe = DB::describeTable($sTable);

			foreach((array)$aDescribe as $aField) {
				if(
					$aField['COLUMN_NAME'] == 'idClient' ||
					$aField['COLUMN_NAME'] == 'client_id' ||
					$aField['COLUMN_NAME'] == 'office'
				) {
					$aClientTables[$sTable] = $aField['COLUMN_NAME'];
					break;
				}
			}

		}

		foreach((array)$aClientTables as $sTable=>$sField) {

			Ext_Thebing_Util::backupTable($sTable);

			$sSql = "
					DELETE FROM
						#table
					WHERE
						#field NOT IN (:clients) AND
						#field != 0
					";

			$aSql = array(
				'table'=>$sTable,
				'field'=>$sField,
				'clients'=>$aClients
			);

			DB::executePreparedQuery($sSql, $aSql);

			$sSql = "OPTIMIZE TABLE #table";

			$aSql = array(
				'table'=>$sTable
			);
			DB::executePreparedQuery($sSql, $aSql);

		}

		return true;

	}

}
