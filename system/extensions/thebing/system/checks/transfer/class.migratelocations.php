<?php

class Ext_Thebing_System_Checks_Transfer_MigrateLocations extends GlobalChecks {

	public function getTitle() {
		return 'Transfer locations migration';
	}

	public function getDescription() {
		return 'Make transfer locations and terminals internationalized and available in all schools.';
	}

	public function executeCheck() {

		$aTables = DB::listTables();
		if(!in_array('kolumbus_airports', $aTables)) {
			return true;
		}

		Util::backupTable('kolumbus_airports');
		Util::backupTable('kolumbus_airports_additional');

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`kap`.*
			FROM
				`kolumbus_airports` `kap` INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `kap`.`idPartnerschool` AND
					`cdb2`.`active` = 1
			WHERE
				`kap`.`active` = 1
		";

		$aAirports = (array)DB::getQueryRows($sSql);
		foreach($aAirports as $aAirport) {

			$sSql = "
				INSERT INTO
					`ts_transfer_locations`
				SET
					`id` = :id,
					`changed` = :changed,
					`created` = :created,
					`creator_id` = :creator_id,
					`editor_id` = :user_id,
					`active` = 1,
					`valid_until` = :valid_until,
					`position` = :position,
					`short` = :airport,
					`address` = :address
			";

			DB::executePreparedQuery($sSql, $aAirport);
			$iLocationId = $aAirport['id'];

			// Schule ergänzen
			$sSql = "
				INSERT INTO
					`ts_transfer_locations_schools`
				SET
					`location_id` = {$iLocationId},
					`school_id` = :idPartnerschool
			";

			DB::executePreparedQuery($sSql, $aAirport);

			// I18N: Bisher gab es nur airport_en als Name
			$this->insertI18NData('ts_transfer_locations_i18n', 'location_id', $iLocationId, $aAirport['airport_en']);

			$this->migrateTerminals($aAirport['id'], $iLocationId);

		}

		DB::commit(__CLASS__);

		DB::executeQuery("DROP TABLE `kolumbus_airports`");
		DB::executeQuery("DROP TABLE `kolumbus_airports_additional`");

		return true;

	}

	/**
	 * @param int $iLocationId
	 */
	private function migrateTerminals($iLocationId) {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_airports_additional`
			WHERE
				`airport_id` = {$iLocationId} AND
				`active` = 1
		";

		$aTerminals = (array)DB::getQueryRows($sSql);
		foreach($aTerminals as $aTerminal) {

			$sSql = "
				INSERT INTO
					`ts_transfer_locations_terminals`
				SET
					`id` = :id,
					`changed` = :changed,
					`created` = :created,
					`creator_id` = :creator_id,
					`editor_id` = :user_id,
					`active` = 1,
					`location_id` = {$iLocationId},
					`short` = :short
			";

			DB::executePreparedQuery($sSql, $aTerminal);
			$iTerminalId = $aTerminal['id'];

			$this->insertI18NData('ts_transfer_locations_terminals_i18n', 'location_terminal_id', $iTerminalId, $aTerminal['description']);

		}

	}

	/**
	 * @param string $sTable
	 * @param string $sForeignKey
	 * @param int $iForeignKeyId
	 * @param string $sName
	 */
	private function insertI18NData($sTable, $sForeignKey, $iForeignKeyId, $sName) {

		$aLanguages = Ext_Thebing_Util::getTranslationLanguages();

		foreach($aLanguages as $aLanguage) {
			$sSql = "
				INSERT INTO
					`{$sTable}`
				SET
					`{$sForeignKey}` = :id,
					`language_iso` = :language_iso,
					`name` = :name
			";

			DB::executePreparedQuery($sSql, [
				'id' => $iForeignKeyId,
				'language_iso' => $aLanguage['iso'],
				'name' => $sName
			]);
		}

	}

}
