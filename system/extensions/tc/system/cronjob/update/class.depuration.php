<?php

class Ext_TC_System_Cronjob_Update_Depuration extends Ext_TC_System_CronJob_Update {
	
	public function executeUpdate() {	
		global $aDebug;
		
		set_time_limit(7200);
		ini_set("memory_limit", '2G');

		try {
		
			// Cronjob-Log aufräumen
			$sSql = "
				DELETE FROM
					`tc_system_cronjobs` 
				WHERE 
					`created` < NOW() - INTERVAL 14 DAY";
			DB::executeQuery($sSql);

			$iDepurationMonth = (int) Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getBackupTablesDepurationMonth');

			$this->cleanUpBackupTables($iDepurationMonth);
			
		} catch (Exception $e) {
			
			self::logError('Ext_TC_System_Cronjob_Update_Depuration - Exception', array('exception' => $e->getMessage()));
			Ext_TC_Util::reportError('Externeserver Updatescript - Fehler', $e);
			
		} catch (Error $e) {

			self::logError('Ext_TC_System_Cronjob_Update_Depuration - Error', array('exception' => $e->getMessage()));
			Ext_TC_Util::reportError('Externeserver Updatescript - Fehler', $e);
			
		}
	}
	
	/**
	 * Löscht veraltete Backup-Tabellen
	 * 
	 * @global array $db_data
	 * @param int $iDepurationMonth
	 * @return bool|array
	 */
	protected function cleanUpBackupTables($iDepurationMonth) {
		global $db_data;
		
		if($iDepurationMonth === 0) {
			return false;
		}
		
		$aSql = array(
			"database" => $db_data["system"]
		);

		// Nur die ältesten 50 Tabellen, damit das nicht so lange dauert.
		$sSql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :database AND TABLE_NAME REGEXP '^__[0-9]{14}.*$' ORDER BY CREATE_TIME ASC LIMIT 50";
		
		$aBackupTables = (array)DB::getQueryCol($sSql, $aSql);

		$aDeleted = array();
		foreach($aBackupTables as $sBackupTable) {

			$aSql = array(
				"table" => $sBackupTable
			);

			$sSql = "SHOW TABLE STATUS LIKE :table";

			$aBackupTableData = DB::getQueryRow($sSql, $aSql);

			$oWDDate = new WDDate($aBackupTableData['Create_time'],  WDDate::DB_DATETIME);
			$oWDDate->add((int) $iDepurationMonth, WDDate::MONTH);
				
			$iCompare = $oWDDate->compare(new WDDate());

			if($iCompare < 0) {
				$aSql = array(
					"table" => $aBackupTableData['Name']
				);

				$sSql = "DROP TABLE #table";

				DB::executePreparedQuery($sSql, $aSql);

				$aDeleted[] = $aBackupTableData['Name'];
			}

		}
		
		self::log('Ext_TC_System_Server_Update_Depuration - Backuptables', $aDeleted);	
		
		return $aDeleted;
	}
	
}