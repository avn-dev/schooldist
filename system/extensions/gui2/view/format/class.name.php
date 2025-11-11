<?php

class Ext_Gui2_View_Format_Name extends Ext_Gui2_View_Format_Abstract { 

	protected $_sLastname = 'lastname';
	protected $_sFirstname = 'firstname';

	public function  __construct($sFirstname=false, $sLastname=false) {
		if($sLastname !== false) {
			$this->_sLastname	= $sLastname;
		}
		if($sFirstname !== false) {
			$this->_sFirstname	= $sFirstname; 
		}
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sLastname = $aResultData[$this->_sLastname];
		$sFirstname = $aResultData[$this->_sFirstname];

		$sName = "";

		if(
			$sLastname &&
			$sFirstname
		) {
			$sName = $sLastname.", ".$sFirstname;
		} elseif(
			$sLastname
		) {
			$sName = $sLastname;
		} else {
			$sName = $sFirstname;
		}

		return $sName; 

	}

}
