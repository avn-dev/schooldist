<?php

class Ext_Thebing_Gui2_Icon_Cheque extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(count($aSelectedIds) > 0) {
			return 1;
		}

		return $oElement->active;


		

	}

}