<?php


class Ext_TC_System_Checks_DeleteFiles extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Delete unused files';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Removes unneeded files and directories.';
		return $sDescription;
	}

	public function isNeeded() {

		return true;
	
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		$aFiles = array(
			'/dev.core.thebing.com/system/extensions/tc/system/class.news.php',
			'/dev.core.thebing.com/system/extensions/tc/system/class.update.php',
			'/dev.core.thebing.com/system/extensions/tc/system/news'
		);

		foreach((array)$aFiles as $sFile) {
			$sFile = \Util::getDocumentRoot(false).$sFile;
			Ext_TC_Util::recursiveDelete($sFile);
		}

		return true;

	}

}