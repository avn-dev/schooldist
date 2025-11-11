<?php

/**
 * Missbraucht Update-Requirements zum Löschen von Dateien vor dem Ausführen des Updates
 */
class Updates_Requirements_DeleteFilePreUpdate extends Requirement {

	protected $aFiles = array(
		'/system/bundles/Gui2/Resources/config/composer.json'
	);

	/**
	 * Löscht alle Dateien im Array aFiles
	 * Wichtig: Falls die Datei nicht da ist, passiert nichts. 
	 * Das Skript soll nur dafür sorgen, dass die Datei nicht da ist.
	 * 
	 * @return boolean
	 */
	public function checkSystemRequirements() {
		
		$sDocumentRoot = Util::getDocumentRoot(false);
		
		foreach($this->aFiles as $sFile) {
			if(is_file($sDocumentRoot.$sFile)) {
				unlink($sDocumentRoot.$sFile);
			}
		}

		return true;
	}
}
	