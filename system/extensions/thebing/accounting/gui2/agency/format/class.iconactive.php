<?php

class Ext_Thebing_Accounting_Gui2_Agency_Format_IconActive extends Ext_Gui2_View_Icon_Active {

	/**
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param $oElement
	 * @return int
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			$oElement->action == 'invoice_only_cn' &&
			count($aSelectedIds) > 1
		) {
			return false;
		}

		// Da der Bezahldialog nur mit einer selben Inquiry-ID gleichzeitig umgehen kann, muss das hier abgefangen werden
		if($oElement->action === 'payment') {
			$aInquiryIds = $this->_oGui->decodeId($aSelectedIds, 'entity_id');
			if (count(array_unique($aInquiryIds)) > 1) {
				return false;
			}
		}

		return true;

	}

}