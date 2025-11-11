<?php 

class Ext_TC_System_Checks_Purifier extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Checks Purifier settings';
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
		ini_set("memory_limit", '512M');
		
		$sPath = Util::getDocumentRoot().'system/includes/htmlpurifier/HTMLPurifier/DefinitionCache/Serializer';	
		
		$bSuccess = Util::checkDir($sPath);
		
		$sDummyFile = $sPath.'/dummy.txt';
		touch($sDummyFile);
		@chmod($sDummyFile, 0777);

		return $bSuccess;
		
	}
	
}
