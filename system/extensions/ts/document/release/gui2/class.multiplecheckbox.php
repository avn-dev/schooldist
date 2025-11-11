<?php

class Ext_TS_Document_Release_Gui2_MultipleCheckbox extends Ext_Gui2_View_MultipleCheckbox_Abstract {
	
	public function getStatus($iRowID, &$aColumnList, &$aResultData) {
		
		// Alle Checkboxen anzeigen, damit man auch mit freigegebenen Dokumenten testen kann
		return 1;
		
		$oDocument = Ext_Thebing_Inquiry_Document::getInstance($aResultData['id']);
		
		if($oDocument->isReleased()) {
			return 0;
		} else {
			return 1;
		}
		
	}
	
}