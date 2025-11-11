<?php

class Ext_Thebing_Gui2_Icon_Accounting_Transfer_Payment extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			$oElement->action == 'export_csv' ||
			$oElement->action == 'toggleMenu' ||
			$oElement->action == ''
		){
			return 1;
		}

		$bPayments = false;

		foreach((array)$aSelectedIds as $iInquiryTransferId) {
			
			$oInquiryTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($iInquiryTransferId);
			$aPayments = $oInquiryTransfer->accounting_payments;
			
			if(!empty($aPayments)){
				$bPayments = true;
			}
			
		}
		
		if($oElement->action == 'transfer_payment') {
			
			// Wenn nichts gewählt ist kann nichts bezahlt werden
			if(count($aSelectedIds) < 1){
				return 0;
			}
			
			if($bPayments){
				return 0;
			} else {
				return 1;
			}
		} else {
			if(!$bPayments){
				return 0;
			} else {
				return 1;
			}
		}

		

	}

}