<?php

class Ext_Thebing_Gui2_Icon_Contract extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		// Löschen nur erlauben, wenn es keine entsprechenden Zusatzvertrag gibt
		if(
			$oElement->task == 'deleteRow'
		) {

			if(empty($aSelectedIds)){
				return 0;
			}

			$oVersion = Ext_Thebing_Contract_Version::getInstance($aRowData[0]['id']);
			$aAdditionalContracts = $oVersion->getAdditionalContracts();
			if(!empty($aAdditionalContracts)) {
				return 0;
			} else {
				return 1;
			}

		// Bestätigen nur erlauben, wenn der Vertrag noch nicht bestätigt ist
		} elseif(
			$oElement->task == 'request' &&
			$oElement->action == 'contract_confirm'
		) {

			if(empty($aSelectedIds)){
				return 0;
			}else{
				return 1; 
			}

			
		}

		if(count($aSelectedIds) > 0) {
		return 1;
		}

		return $oElement->active;
		
	}

}
