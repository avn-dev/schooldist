<?php

class Ext_Thebing_Gui2_Format_Day extends Ext_Gui2_View_Format_Abstract {

	protected $format = 'short';
	protected $days = [];

	public function __construct($sFormat='%A') {
		
		if($sFormat === '%A') {
			$this->format = 'wide';
		}
		
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(empty($this->days)) {
			
			if(empty($this->_sLanguage)) {
				$this->_sLanguage = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool()->getInterfaceLanguage();
			}
			
			$this->days = Ext_Thebing_Util::getLocaleDays($this->_sLanguage, $this->format);
		}
		
		$mValue = $this->days[$mValue]??null;

		return $mValue;
	}

}
