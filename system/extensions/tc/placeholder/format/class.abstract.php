<?php

abstract class Ext_TC_Placeholder_Format_Abstract extends Ext_TC_Gui2_Format {
	
	protected $_aPlaceholder = array();
	
	protected $_sLanguage = '';
	
	public function bindPlaceholder(array $aPlaceholder) {
		$this->_aPlaceholder = $aPlaceholder;
	}
	
	public function setDisplayLanguage($sLanguage) {		
		if(empty($sLanguage)) {
			$sLanguage = Ext_TC_System::getInterfaceLanguage();
		}
		
		$this->_sLanguage = $sLanguage;
	}
	
}
