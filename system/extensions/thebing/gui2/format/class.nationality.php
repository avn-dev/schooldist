<?php

class Ext_Thebing_Gui2_Format_Nationality extends Ext_Gui2_View_Format_Abstract {

	protected $_sInterfaceLanguage = '';

	public function __construct($sInterfaceLanguage = '') {
		global $session_data;

		if(empty($sInterfaceLanguage)) {
			$this->_sInterfaceLanguage = \System::getInterfaceLanguage();
		} else {
			$this->_sInterfaceLanguage = $sInterfaceLanguage;
		}
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aNationalities = Ext_Thebing_Nationality::getNationalities(true, $this->_sInterfaceLanguage, 0);
		return $aNationalities[$mValue];

	}

}
