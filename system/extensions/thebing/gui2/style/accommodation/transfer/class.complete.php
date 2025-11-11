<?php

class Ext_Thebing_Gui2_Style_Accommodation_Transfer_Complete extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aResultData) {

		if (empty($aResultData['transfer_mode'])) {
			return '';
		}

		if($aResultData['arrival_transferdata_exist'] == 1) {
			$sColor = Ext_Thebing_Util::getColor('good');
		} else {
			$sColor = Ext_Thebing_Util::getColor('bad');
		}

		$sReturn = 'background-color: '.$sColor.';';

		return $sReturn;

	}


}
