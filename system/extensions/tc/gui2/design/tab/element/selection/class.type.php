<?php

class Ext_TC_Gui2_Design_Tab_Element_Selection_Type extends Ext_Gui2_View_Selection_Abstract {
	
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		
		$oParent = Ext_TC_Gui2_Design_Tab::getInstance($oWDBasic->tab_id);
		$oDesign = Ext_TC_Gui2_Design::getInstance($oParent->design_id);		

		$oDesigner = Factory::getObject(\Ext_TC_Gui2_Designer::class, [$oDesign->id]);

		$aReturn = $oDesigner->getElementArray(false);

		return $aReturn;
		
	}
	
}
