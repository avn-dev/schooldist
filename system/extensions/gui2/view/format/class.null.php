<?php

class Ext_Gui2_View_Format_Null extends Ext_Gui2_View_Format_Abstract {
		
	/**
	 * Gibt bei einem leeren String (leeres Eingabefeld) null zurück
	 *
	 * @param string $mValue
	 * @param object $oColumn
	 * @param array $aResultData
	 * @return float
	 */
	public function convert($mValue, &$oColumn = null, &$aResultData = null) {

		if (trim($mValue) === '') {
			$mValue = null;
		}

		return $mValue;

	}

}
