<?php

class Ext_TC_Exchangerate_Format_Time extends Ext_TC_Gui2_Format_Date {

	protected $sWDDatePart = WDDate::TIMESTAMP;

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		
		$sReturn = '';
		
		$mValue = (int) $mValue;
		
		if(!empty($mValue)){
		
			if($mValue < 10){
				$mValue = '0'.$mValue;
			}

			$sReturn = $mValue.':00:00 Uhr';
		
		}
		
		return $sReturn;
		
	}
	
}
