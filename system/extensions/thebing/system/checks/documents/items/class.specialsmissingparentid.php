<?php

/**
 * Ticket #12077 – BSC - Specials nicht abgezogen?
 *
 * Bei vielen Specials ist die dringend benötigte parent_id nicht befüllt,
 * wodurch die entsprechenden Amount-Spalten pro Item auch nicht befülllt werden
 * und somit die Specials in den Statistiken nicht abgezogen werden.
 */
class Ext_Thebing_System_Checks_Documents_Items_SpecialsMissingParentId extends GlobalChecks {

	public function getTitle() {
		return 'Invoice item maintenance';
	}

	public function getDescription() {
		return 'Check allocations of specials.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '2048M');

		Util::backupTable('kolumbus_inquiries_documents_versions_items');

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`kidvi`.*,
				`kid`.`type` `document_type`
			FROM
				`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kidvi`.`version_id` AND
					`kidv`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `kidv`.`document_id` AND
					`kid`.`active` = 1
			WHERE
				`kidvi`.`active` = 1 AND
				`kidvi`.`type` = 'special' AND
				`kidvi`.`parent_id` = 0
		";

		$aItems = (array)DB::getQueryRows($sSql);
		$aVersionIds = [];
		$iUpdatedItems = $iErrorItems = 0;

		foreach($aItems as $aItem) {

			$aMatchedItem = null;
			$aItemAdditionalInfo = json_decode($aItem['additional_info'], true);

			$aVersionItems = $this->getItems($aItem['version_id']);
			foreach($aVersionItems as $aVersionItem) {
				$aVersionItemAdditionalInfo = json_decode($aVersionItem['additional_info'], true);

				if(
					!empty($aItemAdditionalInfo['parent_item_key']) &&
					!empty($aVersionItemAdditionalInfo['item_key']) &&
					$aItemAdditionalInfo['parent_item_key'] === $aVersionItemAdditionalInfo['item_key']
				) {
					$aMatchedItem = $aVersionItem;
					break;
				}

			}

			if(!$aMatchedItem) {
				// BSC klickt gerne auch die Specials raus, die auf der Diff wieder auftauchen, aber das Ursprungsitem existiert dort nicht
				$this->logError('No parent item found for special '.$aItem['id'].' (doctype: '.$aItem['document_type'].', created: '.$aItem['created'].')!');
				$iErrorItems++;
				continue;
			}

			$aVersionIds[] = $aItem['version_id'];

			DB::updateData('kolumbus_inquiries_documents_versions_items', [
				'parent_id' => $aMatchedItem['id'],
				// Ext_Thebing_System_Checks_Documents_Items_CreditIndexTimeframes:
				'index_from' => $aMatchedItem['index_from'],
				'index_until' => $aMatchedItem['index_until'],
			], " `id` = {$aItem['id']} ");

			$this->logInfo('Updated special item '.$aItem['id'].': parent_id = '.$aMatchedItem['id']);
			$iUpdatedItems++;

		}

		// Special-Spalten der Parent-Items müssen aktualisiert werden, da diese wieder auf NULL stehen dürften
		$iCounter = 0;
		foreach($aVersionIds as $iVersionId) {
			$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance($iVersionId);
			$oVersion->updateSpecialIndexFields();
			$iCounter++;

			if($iCounter % 100 === 0) {
				WDBasic::clearAllInstances();
			}
		}

		$this->logInfo('Updated '.$iUpdatedItems.' items, '.count($aVersionIds).' versions, '.$iErrorItems.' items with ERROR');

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
				`version_id` = {$iVersionId}
		";

		return (array)DB::getQueryRows($sSql);

	}

}
