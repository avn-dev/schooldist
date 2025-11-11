<?php

namespace Office\Gui2\Format;

class ItemPriceFormat extends PriceFormat {

	public function format($fAmount, &$oColumn = null, &$aResultData = null){
		
		$fAmount = (float)$aResultData['amount'] * (float)$aResultData['price'] * (1-(float)$aResultData['discount_item']/100);
		
		$fAmount = parent::format($fAmount, $oColumn, $aResultData);

		return $fAmount;
	}

}
