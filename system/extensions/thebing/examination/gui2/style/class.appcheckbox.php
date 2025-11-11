<?php

class Ext_Thebing_Examination_Gui2_Style_AppCheckbox extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData) {

		// Bei Einträgen, die durch Terms generiert wurden, keine Checkbox anzeigen
		if($aRowData['examination_id'] === null) {
			return 'visibility: hidden';
		}

		return '';

	}

}
