<?php

/**
 * Formatklasse für die Währungsbezeichnung in Listen
 */
class Ext_TC_Gui2_Format_CurrencyTitle extends Ext_TC_Gui2_Format { 

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oCurrency = Ext_TC_Currency::getInstance((int)$mValue);
		
		$sCurrencyTitle = $oCurrency->getName();
		
		return $sCurrencyTitle;

	}

}