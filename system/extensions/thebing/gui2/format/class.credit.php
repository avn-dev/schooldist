<?php

class Ext_Thebing_Gui2_Format_Credit extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(isset($aResultData['id']) && $aResultData['id'] > 0){

			$fAmountCredit = $aResultData['amount_credit'];

			if($fAmountCredit < 0){

				// Anzeige soll Positiv sein
				$fAmountCredit = $fAmountCredit * -1;

				$sBack = Ext_Thebing_Format::Number($fAmountCredit, $aResultData['currency_id'], $aResultData['school_id']);

				return $sBack;

			} else {
				return '';
			}

		}

	}

	public function align(&$oColumn = null){
		return 'right';
	}
	
}
