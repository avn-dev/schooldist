<?php

class Ext_TC_Gui2_Format_Timestamp extends Ext_TC_Gui2_Format_Date { 

	protected $sWDDatePart = WDDate::TIMESTAMP;

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if($mValue == '0000-00-00' || $mValue == NULL) {
			return '';
		}

		try {
			$mValue = parent::format($mValue, $oColumn, $aResultData);
		} catch(Exception $e) {
			$mValue = $mValue;
		}
		
		return $mValue;

	}
}