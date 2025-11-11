<?php

/**
 * Migriert alle MyISAM-Datenbanktabellen nach InnoDB
 */
class Checks_MigrateToInnoDB extends GlobalChecks {
	
	private $aMyIsamTables = array(
		'kolumbus_logs',
		'language_data',
		'language_files', 
		'system_maillog',
		'system_translations'
	);
	
	public function getTitle() {
		return 'Convert database tables to InnoDB';
	}

	public function getDescription() {
		return 'Convert database tables to InnoDB for transaction-aware functions. This check will take some time.';
	}

	public function executeCheck() {

		set_time_limit(7200);

		$this->changeEngine('InnoDB');
		
		// Check hat zuvor alle Tabellen auf InnoDB umgestellt, neue Blacklist
		$this->changeEngine('MyISAM');
		
		return true;
	}
	
	private function changeEngine($sEngine) {
		global $db_data;
		
		$fTime = microtime(true);
		
		if($sEngine === 'InnoDB') {
			$sSearchEngine = 'MyISAM';
			$sInPart = " NOT ";
		} elseif($sEngine === 'MyISAM') {
			$sSearchEngine = 'InnoDB';
			$sInPart = "";
		} else {
			throw new InvalidArgumentException();
		}
		
		$sSql = "
			SELECT
				TABLE_NAME
			FROM
				information_schema.TABLES
		    WHERE
		    	TABLE_SCHEMA = :database AND
		    	ENGINE = :search_engine AND
		    	TABLE_NAME ".$sInPart." IN( :myisam_tables ) AND
		    	TABLE_NAME NOT REGEXP '^__[0-9]{14}.*$'
		";

		$aResult = (array)DB::getQueryCol($sSql, array(
			'database' => $db_data['system'],
			'myisam_tables' => $this->aMyIsamTables,
			'search_engine' => $sSearchEngine
		));
		foreach($aResult as $sTable) {
			$fStartMigratingTime = microtime(true);
			$this->logInfo('Start migrating table to '.$sEngine.': '.$sTable);

			DB::executeQuery("ALTER TABLE `{$sTable}` ENGINE = '{$sEngine}'");

			$sTookTime = number_format(microtime(true) - $fStartMigratingTime, 3);
			$this->logInfo('Finished migrating table to '.$sEngine.': '.$sTable.' ('.$sTookTime.'s)');
		}

		$sTookTime = number_format(microtime(true) - $fTime, 3);
		$this->logInfo('Took '.$sTookTime.'s');
	}
}