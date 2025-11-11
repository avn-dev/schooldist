<?php

class Ext_Thebing_System_Checks_Inquiry_VisaStatus extends GlobalChecks {

	public function getTitle() {
		return 'Convert visa status fields to flexible fields';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		$aTables = DB::listTables();

		if(in_array('kolumbus_inquiries_additional_documents_relation', $aTables)) {
			Util::backupTable('kolumbus_inquiries_additional_documents_relation');
			DB::executeQuery("DROP TABLE `kolumbus_inquiries_additional_documents_relation`");
		}

		if(!in_array('kolumbus_visum_status_fields', $aTables)) {
			return true;
		}

		Util::backupTable('kolumbus_visum_status_fields');
		Util::backupTable('kolumbus_visum_status_fields_values');
		Util::backupTable('tc_flex_sections_fields');
		#Util::backupTable('tc_flex_sections_fields_values');

		DB::executeQuery("TRUNCATE `kolumbus_visum_status_flex_fields`");

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_visum_status_fields`
			WHERE
				`active` = 1 AND
				`visum_status_id` != 0 AND /* Keine Ahnung, wie das 0 sein kann */
				`type` != 8 /* MS hat nie funktioniert */
		";

		$aFields = (array)DB::getQueryRows($sSql);
		foreach($aFields as $aField) {

			$iFlexFieldId = DB::insertData('tc_flex_sections_fields', [
				#'changed' => $aField['changed'],
				'created' => $aField['created'],
				'creator_id' => $aField['creator_id'],
				'editor_id' => $aField['user_id'],
				'section_id' => 39,
				'title' => $aField['name'],
				'placeholder' => $aField['placeholder'],
				'type' => $aField['type'],
				'position' => $aField['position']
			]);

			DB::insertData('kolumbus_visum_status_flex_fields', [
				'visa_status_id' => $aField['visum_status_id'],
				'flex_field_id' => $iFlexFieldId
			]);

			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_visum_status_fields_values`
				WHERE
					`field_id` = {$aField['id']}
			";

			$aValues = (array)DB::getQueryRows($sSql);
			foreach($aValues as $aValue) {
				// Ext_TC_Flexibility::checkIfEmptyValue() soll ausgeführt werden
				Ext_TC_Flexibility::saveData([$iFlexFieldId => $aValue['value']], $aValue['inquiry_id']);
			}

		}

		DB::commit(__CLASS__);

		DB::executeQuery("DROP TABLE `kolumbus_visum_status_fields`");
		DB::executeQuery("DROP TABLE `kolumbus_visum_status_fields_values`");

		// Da das eine Woche gecached wird und ansonsten die Platzhalter nicht existieren
		WDCache::delete(Ext_TC_Flexibility::PLACEHOLDER_CATEGORIES_CACHE_KEY);

		return true;

	}

}
