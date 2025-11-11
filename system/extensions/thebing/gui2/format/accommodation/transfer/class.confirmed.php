<?php

class Ext_Thebing_Gui2_Format_Accommodation_Transfer_Confirmed extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if (empty($aResultData['transfer_mode'])) {
			return '';
		}

		if(strpos($oColumn->db_column, 'customer_agency') !== false) {
			$sKeyArr = 'arrival_agency_confirmed';
			$sKeyDep = 'departure_agency_confirmed';
		} else {
			$sKeyArr = 'transfer_arr_accommodation_confirmed';
			$sKeyDep = 'transfer_dep_accommodation_confirmed';
		}

		/*
		`kit_arr`.`accommodation_confirmed`					`transfer_arr_accommodation_confirmed`,
				`kit_arr`.`customer_agency_confirmed`				`transfer_arr_customer_agency_confirmed`,
				`kit_dep`.`accommodation_confirmed`					`transfer_dep_accommodation_confirmed`,
				`kit_dep`.`customer_agency_confirmed`				`transfer_dep_customer_agency_confirmed`,

				`kit_arr`.`updated`									`transfer_arr_updated`,
				`kit_dep`.`updated`									`transfer_dep_updated`";*/

		//Datum des Transfers, Uhrzeit, Flugnummer

		$oFormatDate = new Ext_Thebing_Gui2_Format_Date(true);

		$aInfo = array();

		if(!empty($aResultData[$sKeyArr]) && $aResultData[$sKeyArr] != '0000-00-00 00:00:00') {
			$aInfo[] = L10N::t('Ankunft').': '.$oFormatDate->format($aResultData[$sKeyArr]);
		}
		if(!empty($aResultData[$sKeyDep]) && $aResultData[$sKeyDep] != '0000-00-00 00:00:00') {
			$aInfo[] = L10N::t('Abreise').': '.$oFormatDate->format($aResultData[$sKeyDep]);
		}

		$sContent = implode('<br/>', $aInfo);

		return $sContent;

	}

}