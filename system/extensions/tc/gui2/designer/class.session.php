<?php

/**
 * Speichert die GUI2_DIalog Instanzen eines Designs
 * @author Christian Wielath
 */
class Ext_TC_Gui2_Designer_Session {

	public static $sFilePath = '/media/secure/gui2/designer/sessions/';

	public static function buildFileNameWithoutLanguage($iDesign, $aSelectedIds, $aAdditionalDialogPKs = array()){

		$aSelectedIds = (array)$aSelectedIds;
		$sFile = 'design_'.$iDesign.'_entry_'. implode('_', $aSelectedIds);

		if(!empty($aAdditionalDialogPKs)){
			$sFile .= '_pks_'.  implode('_', $aAdditionalDialogPKs);
		}

		return $sFile;
		
	}

	public static function buildFileName($iDesign, $sLanguage, $aSelectedIds, $aAdditionalDialogPKs = array()){
		$sFile = self::buildFileNameWithoutLanguage($iDesign, $aSelectedIds, $aAdditionalDialogPKs);
		$sFile .= '_'.$sLanguage;
		$sFile .= '.sess';
		return $sFile;
	}

	/**
	 * Lädt eine GUI2_Dialog Instanz aus der Datei
	 *
	 * @author Christian Wielath
	 * @param int $iDesign
	 * @return Ext_Gui2_Dialog
	 */
	public static function load($iDesign, $sLanguage, $aSelectedIds, $aAdditionalDialogPKs = array()) {

		$sDir = \Util::getDocumentRoot().self::$sFilePath;

		$sFile = $sDir.self::buildFileName($iDesign, $sLanguage, $aSelectedIds, $aAdditionalDialogPKs);

		if(is_file($sFile)) {

			$iLastTime = filemtime($sFile);

			// Wenn nur wenn es noch nicht älter als 24h ist laden
			if(
				(time() - $iLastTime) <= 86400
			){
				$sContent = file_get_contents($sFile);
				$oInstance = unserialize($sContent);

				if($oInstance instanceof Ext_Gui2_Dialog) {
					return $oInstance;
				}
				
			} else {
				self::delete($iDesign, $aSelectedIds);
			}

		}

		return false;

	}

	/**
	 * Schreibt eine GUI2_Dialog Instanz in eine Datei
	 * @author Christian Wielath
	 * @param Ext_GUI2_Dialog $oInstance
	 * @param int $iDesign
	 * @param string $sLanguage
	 * @return boolean
	 */
	public static function write($oInstance, $iDesign, $sLanguage, $aSelectedIds, $aAdditionalDialogPKs = array()) {

		$sDir = \Util::getDocumentRoot().self::$sFilePath;

		$bSuccess = Util::checkDir($sDir);

		if($bSuccess) {

			$sFile = $sDir.self::buildFileName($iDesign, $sLanguage, $aSelectedIds, $aAdditionalDialogPKs);

			$sContent = serialize($oInstance);

			file_put_contents($sFile, $sContent);

			return true;
		}

		return false;

	}
	
	 /**
	  * Löscht alle Instanzen für einen Entry
	  * @param type $iDesign
	  * @param type $sLanguage
	  */
	public static function delete($iDesign, $aSelectedIds, $aAdditionalDialogPKs = array()) {

		$sDir = \Util::getDocumentRoot().self::$sFilePath;

		$aFiles = (array)glob($sDir.self::buildFileNameWithoutLanguage($iDesign, $aSelectedIds, $aAdditionalDialogPKs).'_*.sess');

		foreach($aFiles as $sFile) {
			if(is_file($sFile)) {
				unlink($sFile);
			}
		}

	}

	/**
	 * Resetet alles Instanzen eines Designs
	 * @param type $iDesign
	 */
	public static function reset($iDesign){

		$sDir = \Util::getDocumentRoot().self::$sFilePath;

		$aFiles = (array)glob($sDir.'design_'.$iDesign.'_*.sess');

		foreach($aFiles as $sFile) {
			if(is_file($sFile)) {
				unlink($sFile);
			}
		}

	}

}