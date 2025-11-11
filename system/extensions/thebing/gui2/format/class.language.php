<?php

class Ext_Thebing_Gui2_Format_Language extends Ext_Thebing_Gui2_Format_Format {

	protected $_sLang = '';

	public function __construct($lang = '') {

		$this->_sLang = $lang;
		
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(empty($this->_sLang)) {
			$oSchool = Ext_Thebing_School::getInstance($aResultData['school_id']);
			$this->_sLang = $oSchool->getInterfaceLanguage();
		}

		$mValue = Ext_Thebing_Util::getLanguageName($mValue, $this->_sLang);

		return $mValue;
		
	}

}
