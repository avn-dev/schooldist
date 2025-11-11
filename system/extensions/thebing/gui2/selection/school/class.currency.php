<?php

class Ext_Thebing_Gui2_Selection_School_Currency extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$oSchool = $oWDBasic->getSchool();

		if(!$oSchool->exist()) {
			return [];
		}

		$oCurrency = new Ext_Thebing_Currency_Util($oSchool);

		$aCurrency = $oCurrency->getCurrencyList(2);

		return $aCurrency;
	}

}