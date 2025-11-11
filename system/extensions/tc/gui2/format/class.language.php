<?php

class Ext_TC_Gui2_Format_Language extends Ext_TC_Placeholder_Format_Abstract {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		if(!empty($this->_sLanguage)) {
			$aList = Ext_TC_Language::getSelectOptions($this->_sLanguage);
		} else {
			$aList = Ext_TC_Language::getSelectOptions();
		}

		if(isset($aList[$mValue])) {
			return $aList[$mValue];
		}
		
		return '';
	}

}
