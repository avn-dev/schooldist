<?php

class Ext_Thebing_Gui2_Format_Currency extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(is_numeric($mValue)) {
			$oCurrency = Ext_Thebing_Currency::getInstance($mValue);
		} else {
			$oCurrency = Ext_Thebing_Currency::getByIso($mValue);
		}

		return $oCurrency->sign;
	}
}