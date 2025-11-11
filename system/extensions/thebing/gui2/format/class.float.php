<?php

class Ext_Thebing_Gui2_Format_Float extends Ext_Thebing_Gui2_Format_Format {

	protected $_iDecimalPlaces = 2;
	protected $_bDotZeros;
	protected $nullValue;

	public function  __construct($iDecimalPlaces = 2, $bDotZeros = true,  $nullValue = false) {
		$this->_iDecimalPlaces = $iDecimalPlaces;
		$this->_bDotZeros		= $bDotZeros;
		$this->nullValue = $nullValue;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if (is_array($mValue)) {
			return array_map(fn ($loop) => $this->format($loop, $oColumn, $aResultData), $mValue);
		}

		if (
			$this->nullValue &&
			$mValue === null
		) {
			return null;
		}

		$mValue = Ext_Thebing_Format::Number($mValue, null, (int)($aResultData['school_id'] ?? 0), $this->_bDotZeros, $this->_iDecimalPlaces);

		return $mValue;
	}

	// Wandelt den wert wieder in den ursprungswert um
	public function convert($mValue, &$oColumn = null, &$aResultData = null){

		if(is_array($mValue)) {

			foreach($mValue as &$iValue) {
				$iValue = $this->convert($iValue, $oColumn, $aResultData);
			}

		} else {

			if(isset($aResultData['school_id'])) {
				$oSchool = Ext_Thebing_School::getInstance($aResultData['school_id']);
			} else {
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
			}

			$aFormat = $oSchool->getNumberFormatData();

			// Check format
			$bCheck = preg_match('/^[\-]?([0-9]+)(\\'.$aFormat['t'].'[0-9]{3})*(\\'.$aFormat['e'].'[0-9]{1,'.$this->_iDecimalPlaces.'})?$/', $mValue);
			if(!$bCheck) {
				return $mValue;
			}

			$mValue = str_replace($aFormat['t'], '', $mValue);
			$mValue = str_replace($aFormat['e'], '.', $mValue);

		}
			
		return $mValue;

	}

	public function align(&$oColumn = null){
		return 'right';
	}

}