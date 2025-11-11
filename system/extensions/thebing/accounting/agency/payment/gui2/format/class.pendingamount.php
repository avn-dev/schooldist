<?php

class Ext_Thebing_Accounting_Agency_Payment_Gui2_Format_PendingAmount extends Ext_Thebing_Gui2_Format_Amount {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if(
			$aResultData['payment_type_id'] == 4 ||
			$aResultData['payment_type_id'] == 5
		) {
			// Bei einer CN-Auszahlung sollen die Werte der CN genommen werden
			$mValue = $aResultData['document_amount'] - ($aResultData['document_payed'] * -1);
		} else {
			// Bei normalen Rechnungen kann man die Infos direkt aus der ts_inquiries entnehmen
			$mValue = $aResultData['document_balance'];
		}

		return parent::format($mValue, $oColumn, $aResultData);

	}

}