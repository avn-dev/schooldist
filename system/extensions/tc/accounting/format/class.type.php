<?php

/**
 *  Format-Klasse für die Art des Kontos bzw. der Zwischenkategorie
 */

class Ext_TC_Accounting_Format_Type extends Ext_Gui2_View_Format_Abstract {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$sString = '';

		$aArray = (array) Ext_TC_Accounting_Accountscode::getTypes();

		if(array_key_exists($mValue, $aArray)){
			$sString = $aArray[$mValue];
		}

		
		return $sString;
		
	}
	
}
