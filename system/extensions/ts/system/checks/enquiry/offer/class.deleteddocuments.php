<?php

/**
 * Check zum Löschen von Dokumenten, deren Angebot gelöscht wurde
 */
class Ext_TS_System_Checks_Enquiry_Offer_DeletedDocuments extends GlobalChecks {

	public function getTitle() {
		return 'Delete documents of deleted offers';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		// Dokumente sind noch über ts_etd verknüpft
		$sSql = "
			SELECT
				`kid`.`id`,
				`kid`.`document_number`,
				`ts_etd`.`enquiry_id`
			FROM
				`kolumbus_inquiries_documents` `kid` INNER JOIN
				`ts_enquiries_to_documents` `ts_etd` ON
					`ts_etd`.`document_id` = `kid`.`id` LEFT JOIN
				`ts_enquiries_offers_to_documents` `ts_eotd` ON
					`ts_eotd`.`document_id` = `kid`.`id`
			WHERE
				`kid`.`type` IN ('brutto', 'netto') AND
				`kid`.`active`= 1 AND
				`kid`.`inquiry_id` = 0 AND
				`ts_eotd`.`enquiry_offer_id` IS NULL
		";

		$aDocuments = (array)DB::getQueryRows($sSql);

		foreach($aDocuments as $aRow) {
			$oDocument = Ext_Thebing_Inquiry_Document::getInstance($aRow['id']);
			$oDocument->delete();

			$this->logInfo('Deleted document '.$aRow['id'].' ('.$aRow['document_number'].') of enquiry '.$aRow['enquiry_id']);
		}

		Ext_Gui2_Index_Stack::executeCache();

		return true;
	}

}