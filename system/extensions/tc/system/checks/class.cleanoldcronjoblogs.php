<?php

class Ext_TC_System_Checks_CleanOldCronjobLogs extends GlobalChecks {

	/**
	 * Gibt den Titel zurück
	 *
	 * @return string
	 */
	public function getTitle() {
		$sTitle = 'Clean deprecated files';
		return $sTitle;
	}

	/**
	 * Gibt die Beschreibung zurück
	 *
	 * @return string
	 */
	public function getDescription() {
		$sDescription = 'Removes legacy cronjob logs';
		return $sDescription;
	}

	/**
	 * Führt die Bereinigung durch und löscht alle Dev-Dateien auf der Installation
	 *
	 * @return bool
	 */
	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		$aFiles = array(
			'/storage/cronjobs/log'
		);

		foreach((array)$aFiles as $sFile) {
			$sFile = \Util::getDocumentRoot(false).$sFile;
			Ext_TC_Util::recursiveDelete($sFile);
		}

		return true;
	}

}
