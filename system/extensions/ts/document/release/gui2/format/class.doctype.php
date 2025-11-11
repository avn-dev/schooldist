<?php

class Ext_TS_Document_Release_Gui2_Format_DocType extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if ($mValue === 'creditnote') {
			// Bei der Stornierung der Creditnote steht leider im Typ "Creditnote" obwohl es sich dabei
			// um eine Stornierung der Creditnote handelt, darum ändern wir hier den Dok-Typen
			$oDocument = Ext_Thebing_Inquiry_Document::getInstance($aResultData['id'] ?? 0);
			$oParentDocument = $oDocument->getParentDocument();
			if ($oParentDocument) {
				if ($oParentDocument->type == 'storno') {
					$mValue = 'creditnote_cancellation';
				}

			}
		}

		// TODO Keine Ahnung, wer sich das hier mal so ausgedacht hatte, aber richtig ist das so auch nicht
		if ($mValue === 'additional_document' &&
			($oLastVersion = Ext_Thebing_Inquiry_Document::getInstance($aResultData['id'] ?? 0)->getLastVersion()) !== null &&
			$oLastVersion->template_id > 0
		) {
			$oTemplate = new Ext_Thebing_Pdf_Template($oLastVersion->template_id);
			return $oTemplate->name;
		}

		$oDocument = new Ext_Thebing_Inquiry_Document();
		$oDocument->type = $mValue;

		return $oDocument->getLabel();

	}

}
