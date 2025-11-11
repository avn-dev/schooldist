<?php

class Ext_TS_System_Checks_Accommodation_ProviderToMeals extends GlobalChecks {

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Update accommodation provider meals data';
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return 'Adds provider meals data to provider to meals table.';
	}

	/**
	 * ext_6 Spalte der Unterkunftsanbieter Tabelle wird geleert, bestehende Einträge
	 * werden in einer neuen Verknüpfungstabelle eingetragen
	 *
	 * @return boolean
	 */
	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '2048M');

		if(!Util::backupTable('customer_db_4')) {
			return false;
		}

		$sSql = "
			CREATE TABLE IF NOT EXISTS
				`ts_accommodation_providers_to_accommodation_meals`
				(
					`accommodation_provider_id` mediumint(9) NOT NULL,
					`meal_id` mediumint(9) NOT NULL,
					PRIMARY KEY (accommodation_provider_id, meal_id)
				)
				ENGINE=InnoDB
			DEFAULT
				CHARSET=utf8
		";

		DB::executeQuery($sSql);

		$bColumnExist = DB::getDefaultConnection()->checkField('customer_db_4', 'ext_6', true);

		if($bColumnExist) {

			$sSql = "
				SELECT
					`id`, `ext_6`
				FROM
					`customer_db_4`
				WHERE
					`active` = 1
			";
			$aData = DB::getQueryPairs($sSql);

			$aReturn = array();
			foreach($aData as $iAccommodationProviderId => $sMealIds) {
				$aReturn[$iAccommodationProviderId] = (array)json_decode($sMealIds, true);
			}

			$oStmt = DB::getPreparedStatement("
				REPLACE INTO
					`ts_accommodation_providers_to_accommodation_meals`
				SET
					`accommodation_provider_id` = ?,
					`meal_id` = ?
			");

			foreach($aReturn as $iAccommodationProviderId => $aRow) {
				foreach($aRow as $iMealId) {
					if($iMealId === null) {
						$this->logError('No meal entry found for accommodation provider '.$aRow['id']);
					} else {
						DB::executePreparedStatement($oStmt, array(
							(int)$iAccommodationProviderId,
							(int)$iMealId
						));
					}
				}
			}

			// ext_6 Spalte löschen
			$sSql = "ALTER TABLE `customer_db_4` DROP `ext_6`";
			DB::executeQuery($sSql);

		}

		return true;
	}

}