<?php

class Ext_TS_Enquiry_Combination_Gui2_Style_Row extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($value, &$column, &$rowData) {

//		$journey = Ext_TS_Inquiry_Journey::getInstance($rowData['id']);
//		$converted = $journey->type & $journey::TYPE_BOOKING;
//
//		// Bei Gruppen ist wieder einmal Sonderbehandlung nötig: Da der Journey nicht verändert wird, muss anhand des Dokuments geschaut werden
//		if (
//			!$converted &&
//			$journey->getInquiry()->hasGroup() &&
//			($document = $journey->getDocument()) !== null &&
//			!empty($document->child_documents_offer)
//		) {
//			$converted = true;
//		}

		if ($rowData['is_converted']) {
			return 'background: '.Ext_Thebing_Util::getColor('lightgreen').';';
		}

		return '';

	}

}
