<?php

class Ext_TC_System_Checks_Flexibility_CleanDuplicateValues extends GlobalChecks {
	
	public function getTitle() {
		return 'Clean-up individual field values';
	}
		
	public function getDescription() {
		return self::getTitle();
	}
	
	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '2G');

		$bBackup = Ext_TC_Util::backupTable('tc_flex_sections_fields_values');

		if(!$bBackup) {
			__pout('Backup failed');
			return false;
		}
	
		$sTransactionPoint = get_class($this);
		
		try {
			
			DB::begin($sTransactionPoint);
			// Values ohne ItemType holen
			$aEmptyItemTypeValues = $this->getAllEmptyItemTypeValues();
			
			foreach($aEmptyItemTypeValues as $aValue) {
				// Schauen ob ein Eintrag mit den selben Daten und ItemType existiert 
				$aDuplicateEntry = $this->searchDuplicateEntry($aValue['field_id'], $aValue['item_id'], $aValue['language_iso']);
				
				if(!empty($aDuplicateEntry)) {
					// Eintrag ohne ItemType löschen
					$this->deleteValue($aValue['field_id'], $aValue['item_id'], $aValue['language_iso']);
				}
			}

		} catch (Exception $ex) {
			__pout($ex);
			DB::rollback($sTransactionPoint);
			return false;
		}
		
		DB::commit($sTransactionPoint);
		
		return true;
	}

	/**
	 * Liefert alle Values bei denen der ItemType nicht ausgefüllt wurde
	 * 
	 * @return array
	 */
	private function getAllEmptyItemTypeValues() {
		
		$sSql = "
			SELECT
				*
			FROM 
				`tc_flex_sections_fields_values`
			WHERE
				`item_type` = ''
		";
		
		return (array) DB::getQueryData($sSql);
		
	}
	
	/**
	 * Sucht nach einem Eintrag mit dem selben Daten und mit ItemType
	 * 
	 * @param int $iFieldId
	 * @param int $iItemId
	 * @param string $sLanguageIso
	 * @return array
	 */
	private function searchDuplicateEntry($iFieldId, $iItemId, $sLanguageIso) {
		
		$sSql = "
			SELECT
				*
			FROM 
				`tc_flex_sections_fields_values`
			WHERE
				`field_id` = :field_id AND
				`item_id` = :item_id AND
				`language_iso` = :language_iso AND
				`item_type` != ''
		";
		
		return (array) DB::getQueryData($sSql, ['field_id' => $iFieldId, 'item_id' => $iItemId, 'language_iso' => $sLanguageIso]);
		
	}
	
	/**
	 * Löscht einen doppelten Eintrag bei dem der ItemType nicht eingetragen wurde
	 * 
	 * @param int $iFieldId
	 * @param int $iItemId
	 * @param string $sLanguageIso
	 */
	private function deleteValue($iFieldId, $iItemId, $sLanguageIso) {
		$sSql = "
			DELETE FROM 
				`tc_flex_sections_fields_values`
			WHERE
				`field_id` = :field_id AND
				`item_id` = :item_id AND
				`language_iso` = :language_iso AND
				`item_type` = ''
		";
		
		(array) DB::executePreparedQuery($sSql, ['field_id' => $iFieldId, 'item_id' => $iItemId, 'language_iso' => $sLanguageIso]);
	}
	
}

