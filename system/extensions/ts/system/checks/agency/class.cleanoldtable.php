<?php

class Ext_Ts_System_Checks_Agency_Cleanoldtable extends GlobalChecks {
	
	
	public function getTitle() {
		$sTitle = 'Clean database structure';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Drops unneeded agency tables.';
		return $sDescription;
	}

	public function executeCheck() {

		$bBackup = Ext_Thebing_Util::backupTable('kolumbus_customer13_agency_groups');
		
		if(!$bBackup) {
			return false;
		}
		
		$sSql = "DROP TABLE IF EXISTS #table";
		$aSql = array(
			'table' => 'kolumbus_customer13_agency_groups'
		);
		DB::executePreparedQuery($sSql, $aSql);

		return true;
		
	}
	
}