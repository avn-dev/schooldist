<?php

class Ext_CalendarSheet_GarbageCollector {

	public static function touchSession($sHash, $sInstanceHash) {

		$sKey = self::_getKey($sHash, $sInstanceHash);

		if(!isset($_SESSION['calendarsheet_garbagecollector'])) {
			$_SESSION['calendarsheet_garbagecollector'] = array();
		}

		$_SESSION['calendarsheet_garbagecollector'][$sKey] = time();

	}

	public static function clean() {

		// 6 Minuten
		$iCompareTime = time() - (6*60);

		foreach($_SESSION['calendarsheet_garbagecollector'] as $sKey=>$iTime) {
			// Wenn Zeit abgelaufen, Eintrag entfernen
			if($iTime < $iCompareTime) {
				$aKey = self::_parseKey($sKey);

				self::unsetInstance($aKey['hash'], $aKey['instance_hash']);

			}
		}

	}

	public static function unsetInstance($sHash, $sInstanceHash) {
		global $user_data;
		$sKey = self::_getKey($sHash, $sInstanceHash);

		Ext_Gui2_Session::delete($user_data['key'], $sHash, $sInstanceHash);
		unset($_SESSION['calendarsheet_garbagecollector'][$sKey]);

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