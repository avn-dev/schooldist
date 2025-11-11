<?php

namespace Office\Gui2\Format;

class AmountFormat extends PriceFormat {
	
	public function format($fAmount, &$oColumn = null, &$aResultData = null){
		
		$fAmount = parent::format($fAmount, $oColumn, $aResultData);
		
		$sReturn = $fAmount.' '.$aResultData['unit'];
		
		return $sReturn;
	}

}
