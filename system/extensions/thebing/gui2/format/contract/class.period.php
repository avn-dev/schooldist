<?php


/**
 * Soll nicht mehr verwendet werden, da die von-bis spalten seperat dargestellt werden
 *
 * Wenn nirgends im Projekt mehr verwendet, bitte löschen :)
 */
class Ext_Thebing_Gui2_Format_Contract_Period extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oFormat = new Ext_Thebing_Gui2_Format_Date();

		if($oColumn->db_column == 'valid_from') {

			$sValidFrom = $oFormat->format($aResultData['valid_from'], $oColumn, $aResultData);
			$sValidUntil = $oFormat->format($aResultData['valid_until'], $oColumn, $aResultData);

			$mValue = $sValidFrom;

			if(!empty($sValidUntil)) {
				$mValue .= ' - '.$sValidUntil;
			}

		}

		return $mValue;

	}

}
