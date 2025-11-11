<?php

class Ext_Thebing_Accounting_Agency_Payment_Gui2_Icon_Active extends Ext_Gui2_View_Icon_Active {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			$oElement->task == 'deleteRow' ||
			$oElement->action == 'edit'
		) {
			$aData = reset($aRowData);
			
			if(
				abs($aData['amount_used_with_document_creditnotes']) > 0 ||
				abs($aData['amount_used_document_creditnotes']) > 0 ||
				abs($aData['amount_used_manual_creditnotes']) > 0
			) {
				return false;
			}			
		}
		
		return true;
	}
	
}
