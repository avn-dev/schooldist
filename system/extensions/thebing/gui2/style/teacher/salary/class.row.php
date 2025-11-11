<?php

class Ext_Thebing_Gui2_Style_Teacher_Salary_Row extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData){

		$oDate = new WDDate();
		$iFrom = $oDate->compare($aRowData['valid_from'], WDDate::DB_DATE);
		if($aRowData['valid_until'] == '0000-00-00') {
			$iUntil = -1;
		} else {
			$iUntil = $oDate->compare($aRowData['valid_until'], WDDate::DB_DATE);
		}

		// Aktiver Datensatz
		if(
			$iFrom > 0 &&
			$iUntil < 0
		) {
			return 'background-color: '.Ext_Thebing_Util::getColor('marked').'; ';
		}

		return '';

	}

}
