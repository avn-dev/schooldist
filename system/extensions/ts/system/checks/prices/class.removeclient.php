<?php

class Ext_TS_System_Checks_Prices_RemoveClient extends GlobalChecks {

	public function getTitle() {
		return 'Update price structure';
	}
	
	public function getDescription() {
		return 'Improves the prices data structure.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '2G');

		// Schon gelaufen?
		$checkTable = DB::describeTable('kolumbus_course_fee', true);
		
		if(!isset($checkTable['client_id'])) {
			return true;
		}
		
		$tables = [
			'kolumbus_prices_new' => false,
			'kolumbus_accommodation_fee' => false,
			'kolumbus_course_fee' => false,
		];
		
		foreach($tables as $table=>&$backupTable) {
			$backupTable = Ext_Thebing_Util::backupTable($table);
		}
		
		unset($backupTable);
		
		// Backups müssen alle geklappt haben
		if(in_array(false, $tables)) {
			return false;
		}

		// Damit das bei allen drei Tabellen einheitlich ist.
		DB::executeQuery("ALTER TABLE `kolumbus_prices_new` CHANGE `idClient` `client_id` INT(11) NOT NULL");
		DB::executePreparedQuery("ALTER TABLE #backupTablePrices CHANGE `idClient` `client_id` INT(11) NOT NULL", ['backupTablePrices'=>$tables['kolumbus_prices_new']]);
		
		// Darf nicht NULL sein, weil sonst der UNIQUE-Key nicht klappt!
		DB::executeQuery("ALTER TABLE `kolumbus_prices_new` CHANGE `payment_condition_id` `payment_condition_id` INT(11) NOT NULL DEFAULT '0'");
		
		$db = DB::getDefaultConnection();
		
		
		foreach($tables as $table=>$backupTable) {
			
			DB::executePreparedQuery("TRUNCATE TABLE #table", ['table'=>$table]);
			DB::executePreparedQuery("ALTER TABLE #table DROP `client_id`", ['table'=>$table]);
			
		}
		
		$queries = [
			"ALTER TABLE `kolumbus_prices_new` DROP INDEX `idClient`, ADD UNIQUE `unique` (`idSchool`, `idSaison`, `idCurrency`, `idWeek`, `idParent`, `typeParent`, `payment_condition_id`) USING BTREE",
			"ALTER TABLE `kolumbus_prices_new` ADD `changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id`",
			"ALTER TABLE `kolumbus_accommodation_fee` ADD `changed` TIMESTAMP NOT NULL FIRST",
			"ALTER TABLE `kolumbus_course_fee` ADD `changed` TIMESTAMP NOT NULL FIRST",
			"DROP TABLE `kolumbus_prices_general`",
			"DROP TABLE `kolumbus_prices`",
			"DROP TABLE `kolumbus_prices_paket`",
		];
		
		foreach($queries as $query) {
			try {
				DB::executeQuery($query);
			} catch (Exception $e) {
				// Nix machen
			}
		}

		foreach($tables as $table=>$backupTable) {
			
			if($table === 'kolumbus_prices_new') {
				$sqlQuery = "SELECT * FROM #table ORDER BY `id` DESC";
			} else {
				// Die Einträge ohne idClient zuerst, da diese tendenziell aktueller sind
				$sqlQuery = "SELECT * FROM #table ORDER BY `client_id`";
			}
			$collection = $db->getCollection($sqlQuery, ['table'=>$backupTable]);
			
			foreach($collection as $row) {
				
				unset($row['client_id']);
				unset($row['idClient']);
				
				if(
					array_key_exists('payment_condition_id', $row) &&
					$row['payment_condition_id'] === null
				) {
					$row['payment_condition_id'] = 0;
				}
				
				try {
					DB::insertData($table, $row);
				} catch (Exception $e) {
					// Nix machen
				}
				
			}
			
		}
		
		return true;
	}
	
}
