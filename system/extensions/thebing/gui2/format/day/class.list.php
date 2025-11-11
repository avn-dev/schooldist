<?php

class Ext_Thebing_Gui2_Format_Day_List extends Ext_Gui2_View_Format_Abstract {

	protected $format;
	
	public function __construct($format="%A") {
		$this->format = $format;
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(empty($mValue)){
			return '';
		}

		$aDays = explode(',', $mValue);

		$mValue = '';

		$oFormat = new Ext_Thebing_Gui2_Format_Day($this->format);

		foreach((array)$aDays as $iDay){

			$mValue .= $oFormat->format($iDay, $oColumn, $aResultData);

			$mValue .= ', ';
		}

		$mValue = rtrim($mValue, ', ');
		

		return $mValue;

	}

}
