<?php

class Ext_Thebing_Gui2_Icon_VisibleContract extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			$oElement->task == 'request' &&
			$oElement->action == 'contract_confirm'
		) {

			if(empty($aSelectedIds)){
				return 0;
			}else{
				return true; 
			}

			
		}

		return 1;
		
	}

}
