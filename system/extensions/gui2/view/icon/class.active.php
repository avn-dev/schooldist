<?php
class Ext_Gui2_View_Icon_Active extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement){
		return 1;
	}

}