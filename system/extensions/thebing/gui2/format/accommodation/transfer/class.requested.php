<?php

class Ext_Thebing_Gui2_Format_Accommodation_Transfer_Requested extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if (empty($aResultData['transfer_mode'])) {
			return '';
		}

		//Datum des Transfers, Uhrzeit, Flugnummer

		$oFormatDate = new Ext_Thebing_Gui2_Format_Date(false, 0, 'DB_DATETIME');
	
		if(!empty($aResultData['transfer_data_requested']) && $aResultData['transfer_data_requested'] != '0000-00-00') {
			$sContent = $oFormatDate->format($aResultData['transfer_data_requested']);
		}

		return $sContent;

	}

}