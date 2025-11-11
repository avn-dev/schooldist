<?php

class Ext_Thebing_Inquiry_Payment_Gui2_Icon_Active extends Ext_Thebing_Gui2_Icon_Inbox {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {
		$iStatus = parent::getStatus($aSelectedIds, $aRowData, $oElement);

		if(
			$iStatus &&
			$oElement->action === 'payment' &&
			count($aSelectedIds) > 1
		) {
			// Wenn eine Gruppe ausgewählt ist und irgendwas anderes, dann Icon deaktivieren
			foreach($aSelectedIds as $iInquiryId) {
				$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
				if($oInquiry->hasGroup()) {
					$iStatus = 0;
					break;
				}
			}
		}

		return $iStatus;
	}
}