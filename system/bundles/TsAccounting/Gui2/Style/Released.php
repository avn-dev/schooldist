<?php

namespace TsAccounting\Gui2\Style;

class Released extends \Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData) {
		$sColor = 'none';

		// Original-Wert benutzen, da Datum formatiert ist #5900
		if(isset($aRowData[$oColumn->select_column . '_original'])) {
			$mValue = $aRowData[$oColumn->select_column . '_original'];
		}

		// Wenn das Dokument freigegeben wurde, wird die Spalte grün angezeigt
		if($mValue > 0) {
			$sColor = \Ext_Thebing_Util::getColor('good');
		}

		return 'background-color: '.$sColor;
	}

}
