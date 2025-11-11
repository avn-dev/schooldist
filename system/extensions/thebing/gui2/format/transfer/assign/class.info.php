<?php

class Ext_Thebing_Gui2_Format_Transfer_Assign_Info extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		
		$oTransferRequest = Ext_Thebing_Inquiry_Provider_Request::getInstance($aResultData['id']);
		
		$oTransfer = $oTransferRequest->getTransfer();
		
		return $oTransfer->getName();

	}
	
}