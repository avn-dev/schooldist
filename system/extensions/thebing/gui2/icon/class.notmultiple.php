<?php

class Ext_Thebing_Gui2_Icon_NotMultiple extends Ext_Gui2_View_Icon_Abstract {

	/**
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param $oElement
	 * @return int
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			$oElement->action == 'editPDF' ||
			$oElement->action == 'showPDF'
		) {
			if(count($aSelectedIds) > 1) {
				return 0;
			}
			return 1;
		}

		if(
			$oElement->action == 'provider_accepted' &&
			count($aSelectedIds) > 0
		) {
			return 1; 
		}

		if(
			$oElement->task == 'deleteRow' &&
			count($aSelectedIds) == 1
		) {
			return 1;
		}

		return $oElement->active;
	}
}