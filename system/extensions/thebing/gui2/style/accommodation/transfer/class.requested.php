<?php

class Ext_Thebing_Gui2_Style_Accommodation_Transfer_Requested extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aResultData) {

		if (empty($aResultData['transfer_mode'])) {
			return '';
		}

		// Anreisedaten sind vorhanden und müssen nicth mehr angefragt werden
		if($aResultData['arrival_transferdata_exist'] == 1) {
			return '';
		// Daten wurden angefragt
		} elseif(
			!empty($aResultData['transfer_data_requested']) &&
			$aResultData['transfer_data_requested'] != '0000-00-00 00:00:00'
		) {
			$sColor = Ext_Thebing_Util::getColor('good');
		} else {
			$sColor = Ext_Thebing_Util::getColor('bad');
		}

		$sReturn = 'background-color: '.$sColor.';';

		return $sReturn;

	}


}
