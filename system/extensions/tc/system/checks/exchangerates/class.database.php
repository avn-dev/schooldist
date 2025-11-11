<?php

class Ext_TC_System_Checks_Exchangerates_Database extends GlobalChecks {

	public function getTitle()
	{
		$sTitle = 'Exchange rate conversion';
		return $sTitle;
	}

	public function getDescription() 
	{
		$sDescription = 'Prepare the database of exchange rates.';
		return $sDescription;
	}

	public function isNeeded() 
	{
		return true;
	}
	
	public function executeCheck() 
	{		
		set_time_limit(120);
		ini_set("memory_limit", '512M');
		
		// Wurde der Check bereits ausgeführt?
		$bChackOldField = $this->_checkField('tc_exchangerates_tables', 'url');
		if($bChackOldField === false) {
			return true;
		}
		
		// Backups -------------------------------------------------------------
		
		$aTables = array();
		$aTables[] = 'tc_exchangerates_tables';
		$aTables[] = 'tc_exchangerates_tables_rates';

		$aSuccess = array();

		foreach($aTables as $sTable){
			$aSuccess[] = Util::backupTable($sTable);
		}

		if(in_array(false, $aSuccess)){
			throw new Exception('Backup Error!');
		}
		
		DB::begin('convert_exchangerate_database');
		
		// Neue Tabelle erzeugen -----------------------------------------------
		
		$bCreateTable = $this->_createNewTable();
		
		// Wenn Tabelle erstellt wurde
		if($bCreateTable === true) {
			
			// Spalte "source_id" setzen
			$bAddSourceColumn = $this->_addColumn('tc_exchangerates_tables_rates', 'source_id');

			// Wenn column "source_id" vorhanden ist
			if($bAddSourceColumn === true) {
			
				// alle bisherigen Wechselkurstabellen holen
				$aExchangeRates = $this->_getAllExchangeRateTables();
								
				try {
				
					$aSourceSucces = array();
					
					// für jede Wechselkurstabelle ein neues Source-Objekt erzeugen
					foreach($aExchangeRates as $iKey => $aData) {

						$oSource = new Ext_TC_Exchangerate_Table_Source();
						$oSource->active = $aData['active'];
						$oSource->table_id = $aData['id'];
						$oSource->update_time = $aData['update_time'];
						$oSource->name = $aData['name'];
						$oSource->factor = $aData['factor'];
						$oSource->url = $aData['url'];
						$oSource->date_position = $aData['date_position'];
						$oSource->date_format = $aData['date_format'];
						$oSource->container = $aData['container'];
						$oSource->rate = $aData['rate'];
						$oSource->separator = $aData['separator'];
						$oSource->source_currency = $aData['source_currency'];
						$oSource->source_currency_searchterm = $aData['source_currency_searchterm'];
						$oSource->target_currency = $aData['target_currency'];
						$oSource->target_currency_searchterm = $aData['target_currency_searchterm'];
						$oSource->reverse = $aData['reverse'];
						$oSource->divisor_searchterm = $aData['divisor_searchterm'];
						$oSource->divisor = $aData['divisor'];
						$oSource->child_element = $aData['child_element'];

						$oSource->save();

						// Rate-Data dem Source-Objekt zuordnen
						$bUpdateRates = $this->_updateRateData($aData['id'], $oSource->id);

						if($bUpdateRates !== false) {
							$aSourceSucces[] = true;
						} else {
							DB::rollback('convert_exchangerate_database');
							throw new Exception('Unable to update exchange rate data');
						}

					} 
				
					// Wenn Source-Objekte erzeugt wurden, dann nicht mehr benötigte Spalten löschen
					if(!in_array(false, $aSourceSucces)){
						
						$bDropTableFields = $this->_dropColumn('tc_exchangerates_tables', array(
							'factor', 'url', 'date_position', 'date_format', 'container', 'rate',
							'separator', 'source_currency', 'source_currency_searchterm', 'target_currency', 
							'target_currency_searchterm', 'reverse', 'divisor_searchterm', 'divisor', 'child_element',
							'update_time'
						));
						
						if($bDropTableFields !== false) {
							DB::commit('convert_exchangerate_database');
							return true;
						} else {
							DB::rollback('convert_exchangerate_database');
							return false;
						}						
						
					}
					
				} catch(Exception $e) {
					DB::rollback('convert_exchangerate_database');
					__pout($e);
				}
				
			} else {
				DB::rollback('convert_exchangerate_database');
				throw new Exception('Unable to add column "source_id"');
			}
			
		} else {
			DB::rollback('convert_exchangerate_database');
			throw new Exception('Unable to create table "tc_exchangerates_tables_sources"');
		}
		
		return false;
	}
	
	/**
	 * Erstellt die neue Tabelle `tc_exchangerates_tables_sources`
	 * @return boolean 
	 */
	protected function _createNewTable() 
	{		
		$sSql = "
		CREATE TABLE IF NOT EXISTS `tc_exchangerates_tables_sources` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
			`active` tinyint(1) NOT NULL DEFAULT '1',
			`editor_id` int(11) NOT NULL,
			`creator_id` int(11) NOT NULL,
			`table_id` mediumint(9) NOT NULL,
			`update_time`  TINYINT(2) NOT NULL,
			`name` varchar(100) NOT NULL,
			`factor` decimal(16,5) NOT NULL,
			`url` varchar(255) NOT NULL,
			`date_position` varchar(255) NOT NULL,
			`date_format` varchar(10) NOT NULL,
			`container` varchar(255) NOT NULL,
			`rate` varchar(255) NOT NULL,
			`separator` varchar(3) NOT NULL DEFAULT '.',
			`source_currency` varchar(255) NOT NULL,
			`source_currency_searchterm` varchar(100) NOT NULL,
			`target_currency` varchar(255) NOT NULL,
			`target_currency_searchterm` varchar(100) NOT NULL,
			`reverse` mediumint(1) NOT NULL DEFAULT '0',
			`divisor_searchterm` varchar(100) NOT NULL,
			`divisor` varchar(255) NOT NULL,
			`child_element` mediumint(1) NOT NULL DEFAULT '0',
			`position` int(11) NOT NULL,
		PRIMARY KEY (`id`),
		KEY `active` (`active`,`editor_id`,`creator_id`),
		KEY `name` (`name`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;		
		";
		
		$bSucces = DB::executeQuery($sSql);
		
		return $bSucces;
	}
	
	/**
	 * fügt einer Tabelle eine Spalte hinzu, wenn si noch nicht vorhanden ist
	 * @param string $sTable
	 * @param string $sColumn
	 * @param string $sAfter
	 * @return boolean 
	 */
	protected function _addColumn($sTable, $sColumn, $sAfter = 'created') 
	{		

		$bSuccess = DB::addField($sTable, $sColumn, 'MEDIUMINT( 9 ) NOT NULL', $sAfter);
		
		return $bSuccess;
		
	}

	/**
	 * alle Wechselkurstabellen holen
	 * @return array 
	 */
	protected function _getAllExchangeRateTables() 
	{		
		$oDummyExchangerateTable = new Ext_TC_Exchangerate_Table(0);
		$aExchangerateTables = (array) $oDummyExchangerateTable->getArrayList();
		return $aExchangerateTables;
	}
	
	/**
	 * Rate-Data den neuen Source-Objekten zuordnen (waren vorher an Wechselkurstabelle geknüpft)
	 * @param int $iExchangerateTableId
	 * @param int $iExchangerateSourceId
	 * @return boolean 
	 */
	protected function _updateRateData($iExchangerateTableId, $iExchangerateSourceId) 
	{		
		$sSql = "
			UPDATE 
				`tc_exchangerates_tables_rates` 
			SET 
				`source_id` = :iNewId 
			WHERE 
				`table_id` = :iOldId;
		";
		
		$aSql = array(
			'iNewId' => (int) $iExchangerateSourceId,
			'iOldId' => (int) $iExchangerateTableId
		);
		
		$bSuccess = (bool)DB::executePreparedQuery($sSql, $aSql);
		
		return $bSuccess;
		
	}
	
	/**
	 * prüft, ob ein Feld in der Datenbank existiert
	 * @param string $sTable
	 * @param string $sColumn
	 * @return boolean 
	 */
	protected function _checkField($sTable, $sColumn) 
	{
		WDCache::flush();
		$bCheck = DB::getDefaultConnection()->checkField($sTable, $sColumn);
		return $bCheck;
	}
	
	/**
	 * löscht die übergebenen Felder aus der Datenbank
	 * @param string $sTable
	 * @param array $aFields
	 * @return boolean 
	 */
	protected function _dropColumn($sTable, array $aFields) 
	{
		if(empty($aFields)) {
			return false;
		}
		
		// Query aufbauen
		$aTemp = array();
		foreach($aFields as $sField) {
			$bCheckField = $this->_checkField($sTable, $sField);
			if($bCheckField === true) {
				$aTemp[] = "DROP `".$sField."`";
			}
		}		
		
		$sSql = " ALTER TABLE `".$sTable."`
				";
		$sSql .= implode(', ', $aTemp);
		$sSql .= ";";

		$bSuccess = DB::executeQuery($sSql);
		
		return $bSuccess;
	}
	
}
