<?php

class Ext_Thebing_System_Checks_DeleteCurrencyBackups extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Delete unneeded backup tables';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '';
		return $sDescription;
	}

	public function executeCheck(){
		global $db_data;

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$aSql = array(
			"field" => 'Tables_in_'.$db_data["system"]
		);

		$sSql = "SHOW TABLES WHERE #field REGEXP '^__[0-9]{14}_kolumbus_currenc'";

		$aCurrencyBackupTables = (array)DB::getQueryCol($sSql, $aSql);
		
		foreach($aCurrencyBackupTables as $sCurrencyBackupTable) {
			
			$aSql = array(
				"table" => $sCurrencyBackupTable
			);

			$sSql = "DROP TABLE #table";

			DB::executePreparedQuery($sSql, $aSql);

		}

		return true;

	}

}