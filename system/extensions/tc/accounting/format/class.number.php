<?php

/**
 *  Format-Klasse für Kontonummer
 */

class Ext_TC_Accounting_Format_Number extends Ext_Gui2_View_Format_Abstract {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		if($mValue == 0){
			$mValue = '';
		}
		
		return $mValue;
		
	}
	
}