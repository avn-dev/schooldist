<?php


class Ext_Thebing_System_Checks_CleanDatabase extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Check inactive entries';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'No longer existing entries are set to inactive.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$aTableStructure = Ext_Thebing_System_DbStructure::get();
									
		$oClean = new Ext_Thebing_Db_Clean();

		$oClean->execute($aTableStructure);

		return true;

	}

}
