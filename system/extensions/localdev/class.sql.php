<?php

/**
 * Klasse liest automatisch MySQL Strukturänderungen anhand von ALTER Queries 
 * aus einem Verzeichnis und führt diese aus.
 */
class Ext_LocalDev_Sql {
	
	/**
	 * Liest alle SQL-Datei aus einem Verzeichnis aus, die ab einem bestimmten
	 * Zeitpunkt geändert wurden
	 *
	 * @param string $sDir
	 * @return array|bool
	 */
	public static function update($sDir) {
		
		$log = \Log::getLogger('default', 'localdev-sql');
		
		$aDebug = array();
		
		$sConfigKey = 'ext_localdev_sql_last_update_'.$sDir;
		
		// Letztes Datum holen
		$sLastUpdate = System::d($sConfigKey);
		
		$iLastUpdate = null;
		if(empty($sLastUpdate)) {
			mail('localdev@p32.de', 'Ext_LocalDev_Sql::ERROR', 'Missing last update date!');
			return false;
		}

		$oLastUpdate = new WDDate($sLastUpdate, WDDate::DB_DATETIME);
		$iLastUpdate = (int)$oLastUpdate->get(WDDate::TIMESTAMP);
		
		$log->info('Last update', [$sDir, $oLastUpdate->get(WDDate::DB_DATETIME)]);
		
		$sDir = Util::getDocumentRoot().'update_queries/'.$sDir.'/';
		
		$aFiles = glob($sDir.'*.sql');

		// Wenn keine Dateien da sind, dann nix machen
		if(!empty($aFiles)) {
			
			foreach($aFiles as $sFile) {

				$iModified = null;
			
				// Wurde die Datei schon bearbeitet?
				if($iLastUpdate !== null) {
					$iModified = filemtime($sFile);
					
					if($iModified < $iLastUpdate) {
						continue;
					}
					
					$aDebug['file'][] = array(
						'path' => $sFile,
						'modified' => $iModified
					);

				}

				$log->info('File', [$sFile]);
				
				// In einzelne Queries aufsplitten
				$sQueries = file_get_contents($sFile);
				$aQueries = preg_split("/\s*;\s*/", $sQueries, -1 , PREG_SPLIT_NO_EMPTY);

				if(!empty($aQueries)) {

					foreach($aQueries as $sQuery) {

						$sError = null;
						
						// Query ausführen
						try {
							$mReturn = DB::executeQuery($sQuery);
						} catch(\Throwable $e) {
							$sError = $e->getMessage();
							$mReturn = false;
						}

						$log->info('Query', [$sQuery, $mReturn, $sError]);
						
						$aDebug['query'][] = array(
							'query' => $sQuery,
							'success' => $mReturn,
							'error' => $sError
						);					

						// Wenn der Query erfolgreich war, Hook aufrufen
						if($mReturn !== false) {

							System::wd()->executeHook('ext_localdev_sql_query', $sQuery);

						}

					}

				}

			}

		}

		// Zeitpunkt in Config speichern
		$oCurrentUpdate = new WDDate;
		System::s($sConfigKey, $oCurrentUpdate->get(WDDate::DB_DATETIME));

		$log->info('Done');
		
		return $aDebug;
	}
	
}