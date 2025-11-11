<?php

class Ext_Thebing_Insurances_Gui2_Format_PriceWrapper extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$oDummy = null;
		$oData = new Ext_Thebing_Insurances_Gui2_Customer($oDummy);

		$aFakeResult = array($aResultData);
		$aFakeResult = $oData->format($aFakeResult);
		$aFakeResult = reset($aFakeResult);

		if ($oColumn->db_column === 'amount_open') {
			$oJourneyInsurance = Ext_TS_Inquiry_Journey_Insurance::getInstance($aFakeResult['inquiry_insurance_id']);
			$fPaid = $oJourneyInsurance->getPaidAmount();
			$aFakeResult[$oColumn->db_column] = $aFakeResult['price'] - $fPaid;
		}

		if (
			$oColumn->db_column === 'price' ||
			$oColumn->db_column === 'amount_open'
		) {
			$oPriceFormat = new Ext_Thebing_Gui2_Format_Amount();
			return $oPriceFormat->format($aFakeResult[$oColumn->db_column], $oColumn, $aResultData);
		}

		return $aFakeResult[$oColumn->db_column];
	}
}