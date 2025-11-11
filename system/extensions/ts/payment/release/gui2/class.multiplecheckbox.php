<?php


class Ext_TS_Payment_Release_Gui2_MultipleCheckbox extends Ext_Gui2_View_MultipleCheckbox_Abstract {

	public function getStatus($iRowID, &$aColumnList, &$aResultData) {
		$oPayment = Ext_Thebing_Inquiry_Payment::getInstance($aResultData['id']);
		return ($oPayment->isReleased()) ? 0 : 1;
	}

}
