<?php

class Ext_TC_Gui2_Selection_Salutation extends Ext_Gui2_View_Selection_Abstract {
	
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		$aSelection = Ext_TC_Util::getPersonTitles();
		return $aSelection;
	}
	
}
