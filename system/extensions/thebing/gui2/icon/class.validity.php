<?php

class Ext_Thebing_Gui2_Icon_Validity extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			$oElement->task == 'deleteRow' &&
			$oElement->action == ''
		) {
			if($aRowData[0]['valid_until'] == '0000-00-00' && count($aSelectedIds)>0) {
				return 1;
			}

			return 0;

		} elseif(
			$oElement->task == 'openDialog' &&
			$oElement->action == 'edit'
		) {

			if($aRowData[0]['valid_until'] == '0000-00-00' && count($aSelectedIds)>0) {
				return 1;
			}

			return 0;
		}

		return 1;

	}

}