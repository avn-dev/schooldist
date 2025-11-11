<?php

/**
 * Speichert die GUI2 Instanzen
 * @author Mark Koopmann
 */
class Ext_CalendarSheet_Session {
	
	public static $sFilePath = '/storage/calendarsheet/sessions/';
	
	/**
	 * Lädt eine GUI2 Instanz aus der Datei
	 * 
	 * @author Mark Koopmann
	 * @global type $user_data
	 * @param type $sHash
	 * @param type $sInstanceHash
	 * @return Ext_Gui2 
	 */
	public static function load($sHash, $sInstanceHash) {
		global $user_data;
		
		$sDir = \Util::getDocumentRoot().self::$sFilePath;
		
		$sFile = $sDir.$user_data['id'].'_'.$sHash.'_'.$sInstanceHash.'.sess';

		if(is_file($sFile)) {

			touch($sFile);

			$sContent = file_get_contents($sFile);
			$oInstance = unserialize($sContent);

			if($oInstance instanceof Ext_CalendarSheet) {
				return $oInstance;
			}

		}
		
		return false;
		
	}
	
	/**
	 * Schreibt eine GUI2 Instanz in eine Datei
	 * 
	 * @author Mark Koopmann
	 * @global type $user_data
	 * @param type $oInstance 
	 */
	public static function write($oInstance) {
		global $user_data;

		$sDir = \Util::getDocumentRoot().self::$sFilePath;

		$bSuccess = Util::checkDir($sDir);

		if($bSuccess) {

			$sFile = $sDir.$user_data['id'].'_'.$oInstance->hash.'_'.$oInstance->instance_hash.'.sess';

			$sContent = serialize($oInstance);

			file_put_contents($sFile, $sContent);

		}

	}

	/**
	 * Löscht die Datei mit der Instanz
	 * 
	 * @global type $user_data
	 * @param type $sHash
	 * @param type $sInstanceHash 
	 */
	public static function delete($sHash, $sInstanceHash) {
		global $user_data;
		
		$sDir = \Util::getDocumentRoot().self::$sFilePath;
		
		$sFile = $sDir.$user_data['id'].'_'.$sHash.'_'.$sInstanceHash.'.sess';

		if(is_file($sFile)) {

			unlink($sFile);

		}
		
	}
	
	/**
	 * Löscht alle Instanzdateien des aktuellen Users
	 * 
	 * @global type $user_data 
	 */
	public static function reset() {
		global $user_data;
		
		$sDir = \Util::getDocumentRoot().self::$sFilePath;
		
		$aFiles = (array)glob($sDir.$user_data['id'].'_*.sess');

		foreach($aFiles as $sFile) {
			if(is_file($sFile)) {
				unlink($sFile);
			}
		}
		
	}
	
}