<?php

/**
 * Ticket #14630 – Fehler beim Umwandeln von Angeboten
 *
 * index_from / index_until bei aktiven Items setzen, die wegen diversen Problemen nicht gesetzt wurden.
 */
class Ext_Thebing_System_Checks_Documents_Items_IndexTimeframe extends GlobalChecks {

	public function getTitle() {
		return 'Invoice item maintenance';
	}

	public function getDescription() {
		return 'Check time frames of credit invoice items.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '2048M');

		Util::backupTable('kolumbus_inquiries_documents_versions_items');

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`kidvi`.*
			FROM
				`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kidvi`.`version_id` AND
					`kidv`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `kidv`.`document_id` AND
					`kid`.`active` = 1 AND
					`kid`.`type` != 'additional_document' LEFT JOIN
				`ts_enquiries_to_documents` `ts_etd` ON
				    `kid`.`inquiry_id` = 0 AND
					`ts_etd`.`document_id` = `kid`.`id`
			WHERE
			    `kidvi`.`active` = 1 AND (
					`kidvi`.`index_from` = '0000-00-00' OR
					`kidvi`.`index_until` = '0000-00-00'
			    ) AND (
			        `kid`.`inquiry_id` != 0 OR
			        `ts_etd`.`enquiry_id` IS NOT NULL
			    )
		";

		$aItems = (array)DB::getQueryRows($sSql);

		foreach($aItems as $iKey => $aItem) {

//			try {

				$oItem = Ext_Thebing_Inquiry_Document_Version_Item::getObjectFromArray($aItem);
				$oItem->updateItemCache(true);

				DB::updateData('kolumbus_inquiries_documents_versions_items', [
					'index_from' => $oItem->index_from,
					'index_until' => $oItem->index_until
				], [
					'id' => $oItem->id
				]);

				$this->logInfo('Updated timeframe of item '.$oItem->id);

//			} catch(Error $e) {
//
//				$this->logError('Error while updating timeframe of item: '.$e->getMessage(), [$e->getTrace()]);
//
//			}

			if($iKey % 100 == 0) {
				WDBasic::clearAllInstances();
			}

		}

		$this->logInfo('Updated '.count($aItems).' items');

		DB::commit(__CLASS__);

		return true;

	}

}
