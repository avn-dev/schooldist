<?php

class Ext_TC_Gui2_Design_Tab_Element_Selection_Parent_Column extends Ext_Gui2_View_Selection_Abstract {
	
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		
		$oParent = Ext_TC_Gui2_Design_Tab_Element::getInstance($oWDBasic->parent_element_id);
		$iColumns = $oParent->column_count;
		
		$aReturn = array();
	
		for($i = 1; $i <= $iColumns; $i++){
			$sTitle = L10N::t('{count}te Spalte(n)');
			$sTitle = str_replace('{count}', $i, $sTitle);
			$aReturn[$i] = $sTitle;
		}
		
		return $aReturn;
		
	}
	
	public function getDefaultOptions(){
		
		$aReturn = array();		
		for($i = 1; $i <= 2; $i++){
			$sTitle = L10N::t('{count}te Spalte(n)');
			$sTitle = str_replace('{count}', $i, $sTitle);
			$aReturn[$i] = $sTitle;
		}
		
		return $aReturn;
	}
	
}