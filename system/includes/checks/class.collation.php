<?php

class Checks_Collation extends GlobalChecks {

	public function getTitle(): string {
		return 'Table fields collation update';
	}

	public function getDescription(): string {
		return 'Updates table fields to "utf8mb4_general_ci" or "utf8mb4_bin" if already _bin';
	}

	/**
	 * @return boolean
	 */
	public function executeCheck(): bool {

		$tables = DB::listTables();

		// Filter out backup tables
		$tables = array_filter($tables, fn($v) => !str_starts_with($v, '__20'));

		// Update
		foreach ($tables as $table) {
			$this->addProcess(['tableName' => $table]);
		}
		return true;
	}

	/**
	 * Updates the collation of a specific table and its columns if necessary.
	 *
	 * @param string $table The table name.
	 * @throws Exception
	 */
	private function updateTableCollation(string $table): void {

		// Check the table's collation
		$tableStatus = DB::getQueryData("SHOW TABLE STATUS WHERE Name = '{$table}'");

		if (!$tableStatus) {
			throw new Exception("Failed to retrieve table status for {$table}");
		}

		$tableInfo = $tableStatus[0];
		$columns = DB::getQueryData("SHOW FULL COLUMNS FROM `{$table}`");

		$targetCollation = 'utf8mb4_general_ci';

		$hasBackup = false;
		// Update tables
		if ($tableInfo['Collation'] !== $targetCollation) {
			$this->logInfo("{$table} collation is {$tableInfo['Collation']}, changing to ".$targetCollation);
			\Util::backupTable($table);
			$hasBackup = true;
			$result = DB::executeQuery("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE ".$targetCollation);
			if (!$result) {
				throw new Exception("Failed to change collation for `{$table}`");
			}
		} else {
			$this->logInfo("`{$table}` collation is already ".$targetCollation);
		}

		// Update columns
		foreach ($columns as $column) {
			// Ai version, checks if field type can have collation
			// if (str_contains($column['Type'], 'char') || str_contains($column['Type'], 'text')) {

			// My version, checks if field type has collation. If not it either can not have, or it is not set, in that case it takes from table, which we updated already
			if ($column['Collation']) {

				$targetCollation = 'utf8mb4_general_ci';
				if (
					str_ends_with($column['Collation'], '_bin') &&
					$column['Collation'] !== 'utf8mb4_bin'
				) {
					$targetCollation = 'utf8mb4_bin';
				}
				if ($column['Collation'] !== $targetCollation) {
					$this->logInfo("Updating column {$column['Field']} in table {$table} to collation {$targetCollation}");
					// Table has no backup yet
					if (!$hasBackup) {
						\Util::backupTable($table);
						$hasBackup = true;
					}
					
					$nullConstraint = ($column['Null'] === 'YES') ? 'NULL' : 'NOT NULL';
					$defaultValue = ($column['Default'] !== null) ? (strtoupper($column['Default']) === 'CURRENT_TIMESTAMP' ? "DEFAULT CURRENT_TIMESTAMP" : "DEFAULT '{$column['Default']}'") : '';

                    $result = DB::executeQuery("ALTER TABLE `{$table}` MODIFY `{$column['Field']}` {$column['Type']} CHARACTER SET utf8mb4 COLLATE {$targetCollation} {$nullConstraint} {$defaultValue}");
					
					if (!$result) {
						throw new Exception("Failed to change collation for `{$table}`.`{$column['Field']}`");
					}

				}
			}
		}
	}

	public function executeProcess(array $aData): bool {
		try {
			$this->updateTableCollation($aData['tableName']);
		} catch (Exception $ex) {
			$this->logError($ex->getMessage());
			// Was passiert, wenn man in executeProcess false ausgibt? Sollte man das?
			return false;
		}
		return true;

	}
}