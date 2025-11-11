<?php


class Ext_TC_System_Checks_Gui2_Resetconfig extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Delete cache for all Config files';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Delete cache for all Config files.';
		return $sDescription;
	}
 
	public function executeCheck(){

		Ext_TC_Config::deleteCache();
		Ext_Gui2_Config_Parser::clearWDCache();

		return true;
	}

}