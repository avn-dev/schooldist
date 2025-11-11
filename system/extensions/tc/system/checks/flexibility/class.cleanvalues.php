<?php

class Ext_TC_System_Checks_Flexibility_CleanValues extends GlobalChecks {
	
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

		$iCount = $this->getDatabaseCount();
		$this->logInfo('tc_flex_sections_fields_values contains '.$iCount.' items');

		// Gleiche Logik wie bei Ext_TC_Flexibility::checkIfEmptyValue()
		$sSql = "
			DELETE FROM
				`tc_flex_sections_fields_values`
			WHERE
				`value` = '' OR (
					`value` = 0 AND
					`field_id` IN (
						SELECT
							`id`
						FROM
							`tc_flex_sections_fields`
						WHERE
							`type` IN (2, 5) -- Checkbox, Dropdown
					)
				)
		";

		DB::executeQuery($sSql);

		// Erst jetzt ALTER ausfüllen, damit die ohnehin gelöschten Einträge wegfallen
		$aTableDescription = DB::describeTable('tc_flex_sections_fields_values', true);
		if(isset($aTableDescription['created'])) {
			// Spalte entfernen, da diese nirgendswo benutzt wird und auch nicht befüllt wird
			DB::executeQuery("ALTER TABLE `tc_flex_sections_fields_values` DROP `created`");
		}


		$iCounAfter = $this->getDatabaseCount();
		$sPercent = number_format(100 - ($iCounAfter / $iCount * 100), 2);
		$this->logInfo('tc_flex_sections_fields_values contains '.$iCounAfter.' items now, removed '.($iCount - $iCounAfter).' items ('.$sPercent.' %)');

		return true;
	}

	private function getDatabaseCount() {

		$sSql = "
			SELECT
				COUNT(*)
			FROM
				`tc_flex_sections_fields_values`
		";

		return (int)DB::getQueryOne($sSql);

	}

}

