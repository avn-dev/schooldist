<?php

class Ext_Gui2_GarbageCollector {

	public static function touchSession($sHash, $sInstanceHash) {

		$sKey = self::_getKey($sHash, $sInstanceHash);

		if(!isset($_SESSION['gui2_garbagecollector'])) {
			$_SESSION['gui2_garbagecollector'] = array();
		}

		$_SESSION['gui2_garbagecollector'][$sKey] = time();

	}

	public static function clean() {

		// 60 Minuten
		// ich (cw) musste das erhöhen da ich sonst Probleme hab bei Listen die Indizierungen durchführen
		// welche länger als 6min dauern! Danach war jedesmal meine Session weg und meiner weiteren
		// indizierungsschritte liefen in Fatal errors
		$iCompareTime = time() - (6*600);

		foreach($_SESSION['gui2_garbagecollector'] as $sKey=>$iTime) {
			// Wenn Zeit abgelaufen, Eintrag entfernen
			if($iTime < $iCompareTime) {
				$aKey = self::_parseKey($sKey);

				self::unsetInstance($aKey['hash'], $aKey['instance_hash']);

			}
		}

	}

	public static function unsetInstance($sHash, $sInstanceHash) {
		
		$oAccess = Access::getInstance();
		
		$sKey = self::_getKey($sHash, $sInstanceHash);

		Ext_Gui2_Session::delete($oAccess->key, $sHash, $sInstanceHash);
		unset($_SESSION['gui2_garbagecollector'][$sKey]);

	}

	protected static function _getKey($sHash, $sInstanceHash) {
		$sKey = $sHash."_".$sInstanceHash;
		return $sKey;
	}

	protected static function _parseKey($sKey) {

		$aKey = array();

		list($aKey['hash'], $aKey['instance_hash']) = explode("_", $sKey);

		return $aKey;

	}

}