<?php

class Ext_TC_System_Checks_CleanFrontendFile extends GlobalChecks {

	/**
	 * Gibt den Titel zurück
	 *
	 * @return string
	 */
	public function getTitle() {
		$sTitle = 'Clean frontend tool';
		return $sTitle;
	}

	/**
	 * Gibt die Beschreibung zurück
	 *
	 * @return string
	 */
	public function getDescription() {
		$sDescription = 'Removes files and directories.';
		return $sDescription;
	}

	/**
	 * Führt die Bereinigung durch und löscht alle Dev-Dateien auf der Installation
	 *
	 * @return bool
	 */
	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		$aFiles = array(
			'/tools/frontend.php'
		);

		foreach((array)$aFiles as $sFile) {
			$sFile = Util::getDocumentRoot(false).$sFile;
			Ext_TC_Util::recursiveDelete($sFile);
		}

		return true;

	}

}
