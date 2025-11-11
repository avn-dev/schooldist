<?php

/**
 * @see \Ext_TS_Enquiry_Combination_Gui2_Data::isLockedGui()
 * @see \Ext_TS_Enquiry_Combination_Gui2_Data::checkDeleteRow()
 */
class Ext_TS_Enquiry_Combination_Gui2_Icon_Active extends Ext_Gui2_View_Icon_Active {

	public function getStatus(&$selectedIds, &$rowData, &$element) {

		if (count($selectedIds) > 1) {
			return false;
		}

		$journey = Ext_TS_Inquiry_Journey::getInstance(reset($selectedIds));
//		$converted = $journey->getInquiry()->isConverted();
		$hasDocument = $journey->getDocument() !== null;

		// Edit
		if (
			$element->action === 'edit' && (
//				$converted ||
				$hasDocument
			)
		) {
			return false;
		}

		// Delete
		if (
			$element->task === 'deleteRow' && (
//				$converted ||
				$hasDocument ||
				count($journey->getInquiry()->getJourneys()) === 1
			)
		) {
			return false;
		}

//		// Create PDF
//		if (
//			$element->action === 'document_edit' &&
//			$converted
//		) {
//			return false;
//		}

		// Delete PDF
		if (
			$element->action === 'delete_offer_pdf' && (
//				$converted ||
				!$hasDocument
			)
		) {
			return false;
		}

		if (
			$element->action === 'convert_offer_to_inquiry' && (
//				$converted ||
				!$hasDocument
			)
		) {
			return false;
		}

		return true;

	}

}
