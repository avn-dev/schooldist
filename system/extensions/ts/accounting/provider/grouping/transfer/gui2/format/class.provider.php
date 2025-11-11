<?php

class Ext_TS_Accounting_Provider_Grouping_Transfer_Gui2_Format_Provider extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		$sKey = str_replace('provider_', '', $oColumn->db_column);

		// Mal Transferanbieter, mal Unterkunftsanbieter
		if($aResultData['provider_type'] === 'accommodation') {
			$sKey = 'accommodation_provider_'.$sKey;
		} else {
			$sKey = 'transfer_company_'.$sKey;
		}

		return $aResultData[$sKey];
	}

}