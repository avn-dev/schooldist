<?php

/**
 * Ticket #12063 – BSC - Minuswerte im Report (Gruppe)
 *
 * index_from / index_until bei Creditnote-Items auf die Zeiträume der Ursprungsitems (gemachted) setzen
 */
class Ext_Thebing_System_Checks_Documents_Items_CreditIndexTimeframes extends GlobalChecks {

	public function getTitle() {
		return 'Invoice item maintenance';
	}

	public function getDescription() {
		return 'Check time frames of credit invoice items.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '1024M');

		Util::backupTable('kolumbus_inquiries_documents_versions_items');

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`kid`.`id` `main_id`,
				`kid`.`latest_version` `main_latest_version`,
				`kid2`.`id` `credit_id`,
				`kid2`.`latest_version` `credit_latest_version`
			FROM
				`kolumbus_inquiries_documents` `kid` INNER JOIN
				`ts_documents_to_documents` `ts_dtd` ON
					`ts_dtd`.`parent_document_id` = `kid`.`id` AND
					`ts_dtd`.`type` = 'credit' INNER JOIN
				`kolumbus_inquiries_documents` `kid2` ON
					`kid2`.`id` = `ts_dtd`.`child_document_id` AND
					`kid2`.`active` = 1
			WHERE
				`kid`.`active` = 1
		";

		$iDocumentsCount = $iItemsCount = 0;
		$aDocuments = (array)DB::getQueryRows($sSql);

		foreach($aDocuments as $aDocument) {

			$aMainItems = $this->getItems($aDocument['main_latest_version']);
			$aCreditItems = $this->getItems($aDocument['credit_latest_version']);

			$bHasItem = false;

			foreach($aCreditItems as $aCreditItem) {
				foreach ($aMainItems as $aMainItem) {
					if (
						// Item matchen über Beschreibung und Betrag
						$aCreditItem['description'] == $aMainItem['description'] &&
						round($aCreditItem['amount'] * -1, 2) == round($aMainItem['amount']) &&
						\Core\Helper\DateTime::isDate($aMainItem['index_from'], 'Y-m-d') &&
						\Core\Helper\DateTime::isDate($aMainItem['index_until'], 'Y-m-d')
					) {
						if (
							$aCreditItem['index_from'] !== $aMainItem['index_from'] ||
							$aCreditItem['index_until'] !== $aMainItem['index_until']
						) {

							DB::updateData('kolumbus_inquiries_documents_versions_items', [
								'index_from' => $aMainItem['index_from'],
								'index_until' => $aMainItem['index_until']
							], " `id` = {$aCreditItem['id']} ");

							$sLogTimeframeNow = $aMainItem['index_from'].' - '. $aMainItem['index_until'];
							$sLogTimeframeBefore = $aCreditItem['index_from'].' - '.$aCreditItem['index_until'];
							$this->logInfo('Updated '.$aCreditItem['id'].': '. $sLogTimeframeNow. ', desc: "'.$aMainItem['description'].'" (was: '.$sLogTimeframeBefore.'), matched item: '.$aMainItem['id']);

							$iItemsCount++;
							$bHasItem = true;

						}
					}
				}
			}

			if($bHasItem) {
				$iDocumentsCount++;
			}

		}

		$this->logInfo('Updated '.$iItemsCount.' items of '.$iDocumentsCount.' documents');

		DB::commit(__CLASS__);

		return true;

	}

	/**
	 * @param int $iVersionId
	 * @return array
	 */
	private function getItems($iVersionId) {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_inquiries_documents_versions_items`
			WHERE
				`version_id` = {$iVersionId} AND
				/* Zusatzgebühren (und manuelle Positionen) ausschließen, da hier irgendein Chaos ist */
				/* Specials werden wiederum in Ext_Thebing_System_Checks_Documents_Items_SpecialsMissingParentId behandelt */
				`type` IN ('course', 'accommodation', 'transfer', 'insurance', 'extra_nights', 'extra_weeks')
		";

		return (array)DB::getQueryRows($sSql);

	}

}