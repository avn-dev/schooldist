<?php

class Ext_Thebing_Gui2_Style_Accommodation_Transfer_Confirmed extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aResultData) {

		if (empty($aResultData['transfer_mode'])) {
			return '';
		}

		if(strpos($oColumn->db_column, 'customer_agency') !== false) {
			$sItem = 'customer_agency';
		} else {
			$sItem = 'accommodation';
		}

		$sKeyArr = 'transfer_arr_'.$sItem.'_confirmed';
		//$sKeyDep = 'transfer_dep_'.$sItem.'_confirmed';

		if(!empty($aResultData[$sKeyArr]) && $aResultData[$sKeyArr] != '0000-00-00 00:00:00') {

			$bCheck = WDDate::isDate($aResultData[$sKeyArr], WDDate::DB_DATETIME);

			if($bCheck) {

				$oDate = new WDDate($aResultData[$sKeyArr], WDDate::DB_DATETIME);

				$bCheckUpdated = WDDate::isDate($aResultData['arrival_updated'], WDDate::DB_DATETIME);
									
				if($bCheckUpdated) {
					
					$iCompare = $oDate->compare($aResultData['arrival_updated'], WDDate::DB_DATETIME);

					if(
						$iCompare < 0 &&
						$aResultData['arrival_updated'] != '0000-00-00 00:00:00'
					) {
						$sColor = Ext_Thebing_Util::getColor('neutral');
					} else {
						$sColor = Ext_Thebing_Util::getColor('good');
					}

				} else {
					$sColor = Ext_Thebing_Util::getColor('good');
				}

			} else {
				$sColor = Ext_Thebing_Util::getColor('bad');
			}

		} else {
			$sColor = Ext_Thebing_Util::getColor('bad');
		}

		$sReturn = 'background-color: '.$sColor.';';

		return $sReturn;

	}

}
