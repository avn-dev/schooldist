<?php

class Error_Handler {
	
	/**
	 * Speicher der Fehler
	 * @var Error_Message
	 */
	protected static $_aErrors = array();
	
	/**
	 * Erstellt ein Fehlerobjekt
	 * Der Key sorgt dafür, dass gleiche Fehlermeldungen nicht mehrfach aufgeführt werden
	 * 
	 * @param string $sKey 
	 * @return \Error_Message 
	 */
	public static function createError($sKey=null) {

		if($sKey === null) {
			$sKey = Util::generateRandomString(16);
		}

		$oError = new Error_Message();

		self::$_aErrors[$sKey] = $oError;

		return $oError;

	}
	
	/**
	 * Ergänzt die Fehler aus dem Speicher in das übergebene Array
	 * @param array $aErrors 
	 */
	public static function mergeErrors(&$aErrors, $sDefaultL10NPath=null) {
		
		foreach(self::$_aErrors as $oError) {
			
			$aGuiArray = $oError->generateGuiArray($sDefaultL10NPath);
			
			$aErrors[] = $aGuiArray;
			
		}
		
	}
	
	
	/**
	 * Prüft ob der Speicher der Fehler leer ist
	 * @return boolean
	 */
	public static function hasErrors() {
		
		if(!empty(self::$_aErrors)) {
			return true;
		}
		
		return false;
	}
	
}