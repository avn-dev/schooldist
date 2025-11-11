<?php

class Ext_Thebing_Gui2_Icon_Examination_Document extends Ext_Gui2_View_Icon_Abstract {
	
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		$aData = array();

		if(count($aSelectedIds) > 0) {
			$aData = reset($aRowData);
		}

		if(
			$oElement->task === 'deleteRow' && (
				empty($aData['examination_id']) ||
				count($aSelectedIds) > 1
			)
		) {
			return 0;
		}

		if(
			$oElement->action == 'contract_open' &&
			!empty($aData)
		){

			if(
				!isset($aData['examination_id']) ||
				$aData['examination_id'] <= 0
			){
				return 0;
			}
		} else if(
				count($aSelectedIds) > 1 && 
				$oElement->action != 'contract_open'
		){
			return 0;
		} elseif(empty($aSelectedIds)){
			return $oElement->active;
		}

		return 1;
	}

}
