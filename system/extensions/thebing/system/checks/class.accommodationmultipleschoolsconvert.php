<?php

class Ext_Thebing_System_Checks_AccommodationMultipleSchoolsConvert extends GlobalChecks {

	/**
	 * @var string[]
	 */
	private $aBackupTables = [];

	public function getTitle() {
		return 'Allow multiple schools per accommodation resource';
	}

	public function getDescription() {
		return 'Update database structure to allow assignment of accommodation resources to multiple schools.';
	}

	public function executeCheck() {

		// Manuelles Nachtragen der Indexe der Zwischentabellen
		/*
			ALTER TABLE `ts_schools_to_courseunits` ADD INDEX `courseunit_id` (`courseunit_id`);
			ALTER TABLE `ts_schools_to_courseunits` ADD INDEX `school_id` (`school_id`);
			ALTER TABLE `ts_schools_to_accommodations_costs_weeks` ADD INDEX `accommodation_cost_week_id` (`accommodation_cost_week_id`);
			ALTER TABLE `ts_schools_to_accommodations_costs_weeks` ADD INDEX `school_id` (`school_id`);
			ALTER TABLE `ts_schools_to_weeks` ADD INDEX `week_id` (`week_id`);
			ALTER TABLE `ts_schools_to_weeks` ADD INDEX `school_id` (`school_id`);
			ALTER TABLE `ts_schools_to_accommodations_categories` ADD INDEX `accommodation_category_id` (`accommodation_category_id`);
			ALTER TABLE `ts_schools_to_accommodations_categories` ADD INDEX `school_id` (`school_id`);
			ALTER TABLE `ts_schools_to_accommodations_roomtypes` ADD INDEX `accommodation_roomtype_id` (`accommodation_roomtype_id`);
			ALTER TABLE `ts_schools_to_accommodations_roomtypes` ADD INDEX `school_id` (`school_id`);
			ALTER TABLE `ts_schools_to_accommodations_meals` ADD INDEX `accommodation_meal_id` (`accommodation_meal_id`);
			ALTER TABLE `ts_schools_to_accommodations_meals` ADD INDEX `school_id` (`school_id`);
			ALTER TABLE `ts_schools_to_accommodations_costs_categories` ADD INDEX `accommodation_cost_category_id` (`accommodation_cost_category_id`);
			ALTER TABLE `ts_schools_to_accommodations_costs_categories` ADD INDEX `school_id` (`school_id`);
			ALTER TABLE `ts_schools_to_accommodation_providers` ADD INDEX `accommodation_provider_id` (`accommodation_provider_id`);
			ALTER TABLE `ts_schools_to_accommodation_providers` ADD INDEX `school_id` (`school_id`);
			ALTER TABLE `ts_accommodation_categories_to_accommodation_providers` ADD INDEX `accommodation_provider_id` (`accommodation_provider_id`);
			ALTER TABLE `ts_accommodation_categories_to_accommodation_providers` ADD INDEX `accommodation_category_id` (`accommodation_category_id`);
			ALTER TABLE `ts_schools_to_accommodation_providers_payment_categories` ADD INDEX `accommodation_provider_payment_category_id` (`accommodation_provider_payment_category_id`);
			ALTER TABLE `ts_schools_to_accommodation_providers_payment_categories` ADD INDEX `school_id` (`school_id`);
		*/

		set_time_limit(3600);
		ini_set("memory_limit", "1024M");

		$this->aBackupTables = [];

		$aMigrationTables = [
			[
				'delete_invalid_school_assignments' => true,
				'source_table' => 'kolumbus_courseunits',
				'source_primary_key_field' => 'id',
				'source_foreign_key_field' => 'idSchool',
				'target_table' => 'ts_courseunits_schools',
				'target_primary_key_field' => 'courseunit_id',
				'target_foreign_key_field' => 'school_id',
				'drop_source_fields' => [
					'idSchool',
					'idClient',
				],
			], [
				'delete_invalid_school_assignments' => true,
				'source_table' => 'kolumbus_accommodations_costs_weeks',
				'source_primary_key_field' => 'id',
				'source_foreign_key_field' => 'idSchool',
				'target_table' => 'ts_accommodation_costweeks_schools',
				'target_primary_key_field' => 'accommodation_cost_week_id',
				'target_foreign_key_field' => 'school_id',
				'drop_source_fields' => [
					'idSchool',
					'idClient',
				],
			], [
				'delete_invalid_school_assignments' => true,
				'source_table' => 'kolumbus_weeks',
				'source_primary_key_field' => 'id',
				'source_foreign_key_field' => 'idSchool',
				'target_table' => 'ts_weeks_schools',
				'target_primary_key_field' => 'week_id',
				'target_foreign_key_field' => 'school_id',
				'drop_source_fields' => [
					'idSchool',
					'idClient',
				],
			], [
				'delete_invalid_school_assignments' => true,
				'source_table' => 'kolumbus_accommodations_categories',
				'source_primary_key_field' => 'id',
				'source_foreign_key_field' => 'school_id',
				'target_table' => 'ts_accommodation_categories_schools',
				'target_primary_key_field' => 'accommodation_category_id',
				'target_foreign_key_field' => 'school_id',
				'drop_source_fields' => [
					'school_id',
					'idClient',
				],
			], [
				'delete_invalid_school_assignments' => true,
				'source_table' => 'kolumbus_accommodations_roomtypes',
				'source_primary_key_field' => 'id',
				'source_foreign_key_field' => 'school_id',
				'target_table' => 'ts_accommodation_roomtypes_schools',
				'target_primary_key_field' => 'accommodation_roomtype_id',
				'target_foreign_key_field' => 'school_id',
				'drop_source_fields' => [
					'school_id',
					'idClient',
				],
			], [
				'delete_invalid_school_assignments' => true,
				'source_table' => 'kolumbus_accommodations_meals',
				'source_primary_key_field' => 'id',
				'source_foreign_key_field' => 'school_id',
				'target_table' => 'ts_accommodation_meals_schools',
				'target_primary_key_field' => 'accommodation_meal_id',
				'target_foreign_key_field' => 'school_id',
				'drop_source_fields' => [
					'school_id',
					'idClient',
				],
			], [
				'delete_invalid_school_assignments' => true,
				'source_table' => 'kolumbus_accommodations_costs_categories',
				'source_primary_key_field' => 'id',
				'source_foreign_key_field' => 'school_id',
				'target_table' => 'ts_accommodation_costs_categories_schools',
				'target_primary_key_field' => 'accommodation_cost_category_id',
				'target_foreign_key_field' => 'school_id',
				'drop_source_fields' => [
					'school_id',
				],
			], [
				'delete_invalid_school_assignments' => true,
				'source_table' => 'customer_db_4',
				'source_primary_key_field' => 'id',
				'source_foreign_key_field' => 'ext_2',
				'target_table' => 'ts_accommodation_providers_schools',
				'target_primary_key_field' => 'accommodation_provider_id',
				'target_foreign_key_field' => 'school_id',
				'drop_source_fields' => [
					'ext_2',
					'idClient',
				],
			], [
				'source_table' => 'customer_db_4',
				'source_primary_key_field' => 'id',
				'source_foreign_key_field' => 'ext_1',
				'target_table' => 'ts_accommodation_categories_to_accommodation_providers',
				'target_primary_key_field' => 'accommodation_provider_id',
				'target_foreign_key_field' => 'accommodation_category_id',
				'migrate_source_values' => [
					[
						'from' => 'ext_1',
						'to' => 'default_category_id',
					],
				],
				'drop_source_fields' => [
					'ext_1',
				],
			],
		];

		$aAssignToAllSchoolsTables = [
			[
				'source_table' => 'ts_accommodation_providers_payment_categories',
				'source_primary_key_field' => 'id',
				'target_table' => 'ts_accommodation_provider_payment_categories_schools',
				'target_primary_key_field' => 'accommodation_provider_payment_category_id',
				'target_foreign_key_field' => 'school_id',
			],
		];

		$aDropFieldsTables = [
			[
				'source_table' => 'kolumbus_rooms',
				'drop_source_fields' => [
					'idClient',
					'idSchool',
				],
			],
		];

		/*
		 * Diese Migrationen werden effektiv nur einmal ausgeführt (solange die Zieltabelle nicht existiert)
		 * und könnten auch gar nicht mehrfach ausgeführt werden (da die alten Spalten gelöscht werden)
		 */
		foreach($aMigrationTables as $aMigration) {
			$this->executeTableMigration($aMigration);
		}
		foreach($aAssignToAllSchoolsTables as $aMigration) {
			$this->executeAssignToAllSchoolsMigration($aMigration);
		}

		/*
		 * Diese Migrationen werden effektiv nur einmal ausgeführt (solange die Spalten existieren)
		 * und könnten auch gar nicht mehrfach ausgeführt werden (da die Spalten gelöscht werden)
		 */
		foreach($aDropFieldsTables as $aMigration) {
			$this->executeDropSourceFieldsMigration($aMigration);
		}

		/*
		 * Diese Migration kann immer wieder ausgeführt werden, es werden einfach alle Dateien die noch gefunden
		 * werden verschoben
		 */
		$this->executeMoveAccommodationVisitFilesMigration();

		return true;

	}

	private function executeTableMigration(array $aMigration) {

		if(
			Ext_Thebing_Util::checkTableExists($aMigration['target_table']) ||
			!Ext_Thebing_Util::checkTableExists($aMigration['source_table'])
		) {
			return;
		}

		if(!in_array($aMigration['source_table'], $this->aBackupTables)) {
			Ext_Thebing_Util::backupTable($aMigration['source_table']);
			$this->aBackupTables[] = $aMigration['source_table'];
		}

		if(
			isset($aMigration['delete_invalid_school_assignments']) &&
			$aMigration['delete_invalid_school_assignments']
		) {
			$this->deleteInvalidSchoolAssignments($aMigration);
		}

		$this->createTargetTable($aMigration);

		$sSql = "
			INSERT INTO
				`".$aMigration['target_table']."`
			(
				`".$aMigration['target_foreign_key_field']."`,
				`".$aMigration['target_primary_key_field']."`
			)
			SELECT
				`".$aMigration['source_foreign_key_field']."`,
				`".$aMigration['source_primary_key_field']."`
			FROM
				`".$aMigration['source_table']."`
		";
		DB::executeQuery($sSql);

		if(isset($aMigration['migrate_source_values'])) {
			$this->migrateSourceValues($aMigration);
		}

		if(isset($aMigration['drop_source_fields'])) {
			$this->dropSourceFields($aMigration);
		}

	}

	private function executeAssignToAllSchoolsMigration(array $aMigration) {

		if(
			Ext_Thebing_Util::checkTableExists($aMigration['target_table']) ||
			!Ext_Thebing_Util::checkTableExists($aMigration['source_table']) ||
			!Ext_Thebing_Util::checkTableExists('customer_db_2')
		) {
			return;
		}

		if(!in_array($aMigration['source_table'], $this->aBackupTables)) {
			Ext_Thebing_Util::backupTable($aMigration['source_table']);
			$this->aBackupTables[] = $aMigration['source_table'];
		}

		$this->createTargetTable($aMigration);

		$sSql = "
			INSERT INTO
				`".$aMigration['target_table']."`
			(
				`school_id`,
				`".$aMigration['target_primary_key_field']."`
			)
			SELECT
				`customer_db_2`.`id`,
				`".$aMigration['source_table']."`.`".$aMigration['source_primary_key_field']."`
			FROM
				`".$aMigration['source_table']."`
			JOIN
				`customer_db_2`
			WHERE
				`customer_db_2`.`active` = 1
		";
		DB::executeQuery($sSql);

		if(isset($aMigration['migrate_source_values'])) {
			$this->migrateSourceValues($aMigration);
		}

		if(isset($aMigration['drop_source_fields'])) {
			$this->dropSourceFields($aMigration);
		}

	}

	private function executeDropSourceFieldsMigration(array $aMigration) {

		if(!Ext_Thebing_Util::checkTableExists($aMigration['source_table'])) {
			return;
		}

		if(isset($aMigration['migrate_source_values'])) {
			$this->migrateSourceValues($aMigration);
		}

		if(isset($aMigration['drop_source_fields'])) {
			$this->dropSourceFields($aMigration);
		}

	}

	private function executeMoveAccommodationVisitFilesMigration() {

		$aAccommodationVistsFiles = glob(\Util::getDocumentRoot().'storage/clients/client_*/school_*/accommodations/visits/*');

		if(empty($aAccommodationVistsFiles)) {
			return;
		}

		clearstatcache(true);

		$sNewDir = \Util::getDocumentRoot().'storage/accommodations/visits/';
		if(!file_exists($sNewDir)) {
			if(!mkdir($sNewDir, 0777, true)) {
				$sMsg = 'Failed to create directory: '.$sNewDir;
				throw new Exception($sMsg);
			}
		}

		foreach($aAccommodationVistsFiles as $sFile) {
			$sNewFile = $sNewDir.basename($sFile);
			if(file_exists($sNewFile)) {
				$sMsg = 'File already exists: '.$sNewFile;
				throw new Exception($sMsg);
			}
			if(!rename($sFile, $sNewFile)) {
				$sMsg = 'Failed to move file: '.$sFile;
				throw new Exception($sMsg);
			}
		}

	}

	private function createTargetTable(array $aMigration) {

		$sSql = "
			CREATE TABLE
				`".$aMigration['target_table']."`
			(
				`".$aMigration['target_foreign_key_field']."` int(11) NOT NULL,
				`".$aMigration['target_primary_key_field']."` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		DB::executeQuery($sSql);

		$sSql = "
			ALTER TABLE
				`".$aMigration['target_table']."`
			ADD PRIMARY KEY (
				`".$aMigration['target_foreign_key_field']."`,
				`".$aMigration['target_primary_key_field']."`
			)
		";
		DB::executeQuery($sSql);

		$sSql = "
			ALTER TABLE
				`".$aMigration['target_table']."`
			ADD INDEX `".$aMigration['target_foreign_key_field']."` (
				`".$aMigration['target_foreign_key_field']."`
			) 
		";
		DB::executeQuery($sSql);

		$sSql = "
			ALTER TABLE
				`".$aMigration['target_table']."`
			ADD INDEX `".$aMigration['target_primary_key_field']."` (
				`".$aMigration['target_primary_key_field']."`
			) 
		";
		DB::executeQuery($sSql);

	}

	private function dropSourceFields(array $aMigration) {

		foreach($aMigration['drop_source_fields'] as $sDropField) {

			if(DB::getDefaultConnection()->checkField($aMigration['source_table'], $sDropField, true)) {

				if(!in_array($aMigration['source_table'], $this->aBackupTables)) {
					Ext_Thebing_Util::backupTable($aMigration['source_table']);
					$this->aBackupTables[] = $aMigration['source_table'];
				}

				$sSql = "
					ALTER TABLE
						`".$aMigration['source_table']."`
					DROP
						`".$sDropField."`
				";
				DB::executeQuery($sSql);
			}

		}

	}

	private function deleteInvalidSchoolAssignments(array $aMigration) {

		if(!in_array($aMigration['source_table'], $this->aBackupTables)) {
			Ext_Thebing_Util::backupTable($aMigration['source_table']);
			$this->aBackupTables[] = $aMigration['source_table'];
		}

		$sSql = "
			DELETE
				`".$aMigration['source_table']."`
			FROM
				`".$aMigration['source_table']."`
			LEFT OUTER JOIN
				`customer_db_2`
			ON
				`customer_db_2`.`id` = `".$aMigration['source_table']."`.`".$aMigration['source_foreign_key_field']."`
			WHERE
				`customer_db_2`.`id` IS NULL OR
				`customer_db_2`.`active` != 1
		";
		DB::executeQuery($sSql);

	}

	private function migrateSourceValues(array $aMigration) {

		foreach($aMigration['migrate_source_values'] as $aSourceValueMigration) {

			if(!in_array($aMigration['source_table'], $this->aBackupTables)) {
				Ext_Thebing_Util::backupTable($aMigration['source_table']);
				$this->aBackupTables[] = $aMigration['source_table'];
			}

			$sSql = "
				UPDATE
					`".$aMigration['source_table']."`
				SET
					`".$aSourceValueMigration['to']."` = `".$aSourceValueMigration['from']."`,
					`changed` = `changed`
			";
			DB::executeQuery($sSql);

		}

	}

}
