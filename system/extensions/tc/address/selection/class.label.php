<?php

class Ext_TC_Address_Selection_Label extends Ext_Gui2_View_Selection_Abstract {

	
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		
		$aSelection = Ext_TC_Address_Label::getSelectOptions();
		
		if(!empty($aSelection)) {
			$aSelection = Ext_TC_Util::addEmptyItem($aSelection);
		}
		
		return $aSelection;

	}
	
}
?>
