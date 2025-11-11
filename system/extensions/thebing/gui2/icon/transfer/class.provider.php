<?php
/*
 * Die Klasse ist für das De-aktivieren der GUI2 Icons zuständig in der Transferliste
 */
class Ext_Thebing_Gui2_Icon_Transfer_Provider extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if($oElement->action == 'transfer_provider_assign'){

			if(empty($aSelectedIds)){
				return 0;
			}

			return 1;
		}
		
		return $oElement->active;
	}
}

?>