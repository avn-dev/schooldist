<?php

class Ext_Thebing_Gui2_Format_Int extends Ext_Gui2_View_Format_Abstract {

	protected $_iDecimalPlaces = 0;

	public function  __construct($iDecimalPlaces = 0) {
		// TODO In dieser Formatklasse wird _iDecimalPlaces gar nicht benutzt und in der format() Methode / Int() Methode
		// sind die Dezimalstellen mit "0" gehardcodet.
		$this->_iDecimalPlaces = $iDecimalPlaces;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$mValue = Ext_Thebing_Format::Int($mValue, null, (int)$aResultData['school_id']);

		return $mValue;

	}

	// Wandelt den wert wieder in den ursprungswert um
	public function convert($mValue, &$oColumn = null, &$aResultData = null){

		if(isset($aResultData['school_id'])) {
			$oSchool = Ext_Thebing_School::getInstance($aResultData['school_id']);
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		}

		$aFormat = $oSchool->getNumberFormatData();

		// Check format
		$bCheck = preg_match('/^[\-]?([0-9]+)(\\'.$aFormat['t'].'[0-9]{3})*?$/', $mValue);
		if(!$bCheck) {
			return $mValue;
		}

		$mValue = str_replace($aFormat['t'], '', $mValue);

		return $mValue;

	}

	public function align(&$oColumn = null){
		return 'right';
	}

}
