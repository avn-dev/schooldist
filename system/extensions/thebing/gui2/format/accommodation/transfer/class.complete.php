<?php

class Ext_Thebing_Gui2_Format_Accommodation_Transfer_Complete extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if (empty($aResultData['transfer_mode'])) {
			return '';
		}

		//Datum des Transfers, Uhrzeit, Flugnummer

		$oFormatDate = new Ext_Thebing_Gui2_Format_Date(true);
		$oFormatTime = new Ext_Thebing_Gui2_Format_Time();

		$sContent = '';

		if(!empty($aResultData['arrival_date']) && $aResultData['arrival_date'] != '0000-00-00') {
			$sContent .= $oFormatDate->format($aResultData['arrival_date']);
		}
		if(!empty($aResultData['arrival_time']) && $aResultData['arrival_time'] != '00:00:00') {
			if(!empty($sContent)) {
				$sContent .= ' ';
			}
			$sContent .= $oFormatTime->format($aResultData['arrival_time']);
		}
		if(!empty($aResultData['arrival_flightnumber'])) {
			if(!empty($sContent)) {
				$sContent .= ', ';
			}
			$sContent .= $aResultData['arrival_flightnumber'];
		}

		return $sContent;

	}

}