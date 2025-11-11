<?php

class Ext_Thebing_Gui2_Format_Document_MainType extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$languageObject = $this->getLanguageObject('Thebing » PDF');
		
		if(strpos($mValue, 'proforma') !== false) {
			$sType = $languageObject->translate('Proforma');
		} elseif(
			strpos($mValue, 'brutto') !== false ||
			strpos($mValue, 'netto') !== false
		) {
			$sType = $languageObject->translate('Rechnung');
		} elseif(
			strpos($mValue, 'credit') !== false
		) {
			$sType = $languageObject->translate('Gutschrift');
		} elseif(
			strpos($mValue, 'receipt') !== false
		) {
			$sType = $languageObject->translate('Zahlungsbeleg');
		} else {
			$sType = $languageObject->translate('Dokument');
		}

		return $sType;
	}

}
