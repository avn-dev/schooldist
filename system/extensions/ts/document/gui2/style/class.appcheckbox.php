<?php

class Ext_TS_Document_Gui2_Style_AppCheckbox extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData) {

		// Nettorechnung oder Template-Typ ohne mögliche Freigabe für App: Checkbox ausblenden
		if(
			strpos($aRowData['type_original'], 'netto') !== false ||
			!Ext_Thebing_Pdf_Template::checkStudentAppReleaseWhitelist($aRowData['template_type'])
		) {
			return 'visibility: hidden';
		}

		return '';

	}

}
