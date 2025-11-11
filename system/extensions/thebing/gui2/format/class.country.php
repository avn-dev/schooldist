<?php


class Ext_Thebing_Gui2_Format_Country extends Ext_Thebing_Gui2_Format_Format {
	
	protected $_oSchool;
	
	protected $_sInterfaceLanguage = '';

	public function __construct($sInterfaceLanguage = '') {

		$this->_sInterfaceLanguage = $sInterfaceLanguage;
		if(empty($sInterfaceLanguage)) {
			$this->_oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
			$this->_sInterfaceLanguage = $this->_oSchool->getInterfaceLanguage();
		}
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aCountries = Ext_Thebing_Country_Search::getLocalizedCountries($this->_sInterfaceLanguage);

		return $aCountries[$mValue];
		
	}

}