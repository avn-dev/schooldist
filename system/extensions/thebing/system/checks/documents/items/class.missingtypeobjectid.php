<?php

/**
 * Ticket #12092 – Celtic - Neuer Report basierend auf Line items
 *
 * Vom alten Form wurde bei den entsprechenden Items keine type_object_id geschrieben.
 * Demnach sind diese Items bei Spalten mit Gruppierung in der Statistik nicht existent,
 * in der Totale aber schon.
 *
 * Die type_object_id kann man eigentlich nicht korrekt nachträglich setzen, weil sich
 * die jeweilge Kursbuchung usw. verändern kann und die originalen Werte erst seit neustem
 * in additional_info drin stehen. Es ist aber immer noch besser, wenn da irgendeine ID
 * drin steht (die demnach zur Buchung gehört), als wenn type_object_id auf 0 steht.
 */
class Ext_Thebing_System_Checks_Documents_Items_MissingTypeObjectId extends GlobalChecks {

	public function getTitle() {
		return 'Invoice item maintenance';
	}

	public function getDescription() {
		return 'Check allocation of corresponding types (courses, accommodation categories, insurances).';
	}

	public function executeCheck() {

		Util::backupTable('kolumbus_inquiries_documents_versions_items');

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_inquiries_documents_versions_items`
			WHERE
				`type` IN ('course', 'accommodation', 'extra_nights', 'extra_weeks', 'insurance') AND
				`type_object_id` = 0
		";

		$aItems = (array)DB::getQueryRows($sSql);

		$iCounter = $iMissingCount = 0;
		foreach($aItems as $aItem) {
			$oItem = Ext_Thebing_Inquiry_Document_Version_Item::getObjectFromArray($aItem);

			$iTypeObjectId = $this->getTypeObjectId($oItem);

			if(empty($iTypeObjectId)) {
				$this->logError('Item '.$oItem->id.': No type_object_id found! (created: '.$aItem['created'].')');
				$iMissingCount++;
				continue;
			}

			$sSql = "
				UPDATE
					`kolumbus_inquiries_documents_versions_items`
				SET
					`type_object_id` = :type_object_id
				WHERE
					`id` = :item_id
			";

			DB::executePreparedQuery($sSql, [
				'item_id' => $oItem->id,
				'type_object_id' => $iTypeObjectId
			]);

			$this->logInfo('Item '.$oItem->id.': Set type_object_id to '.$iTypeObjectId);

			if($iCounter % 100 == 0) {
				WDBasic::clearAllInstances();
			}

			$iCounter++;

		}

		DB::commit(__CLASS__);

		$this->logInfo('Set type_object_id for '.$iCounter.' items');
		$this->logInfo('Couldn\'t find type_object_id for '.$iMissingCount.' items');
		$this->logInfo('Total items: '.($iCounter + $iMissingCount));

		return true;

	}

	/**
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @return string|int
	 */
	private function getTypeObjectId(Ext_Thebing_Inquiry_Document_Version_Item $oItem) {

		$oService = $oItem->getJourneyService();

		switch($oItem->type) {
			case 'course':
				return $oService->course_id;
			case 'accommodation':
			case 'extra_nights':
			case 'extra_weeks':
				return $oService->accommodation_id;
			case 'insurance':
				return $oService->insurance_id;
		}

		throw new DomainException('Unknown item type');

	}

}