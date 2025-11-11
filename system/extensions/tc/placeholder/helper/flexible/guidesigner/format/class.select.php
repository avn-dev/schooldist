<?php

class Ext_TC_Placeholder_Helper_Flexible_GuiDesigner_Format_Select extends Ext_TC_Placeholder_Format_Abstract {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$iTabElement = (int) $this->_aPlaceholder['element_id'];
		$oTabElement = Ext_TC_Gui2_Design_Tab_Element::getInstance($iTabElement);
		
		$mReturn = $oTabElement->getSelectValue($mValue, $this->_sLanguage);
		
		return $mReturn;
	}
	
}
