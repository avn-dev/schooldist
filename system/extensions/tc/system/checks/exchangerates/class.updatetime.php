<?php

class Ext_TC_System_Checks_Exchangerates_UpdateTime extends GlobalChecks {

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
		$bChackOldField = $this->_checkField('tc_exchangerates_tables', 'update_time');
		if($bChackOldField === true) {
			return true;
		}
		
		// Backup
		$aTables = array();
		$aTables[] = 'tc_exchangerates_tables';
		$aTables[] = 'tc_exchangerates_tables_sources';

		$aSuccess = array();

		foreach($aTables as $sTable){
			$aSuccess[] = Util::backupTable($sTable);
		}

		if(in_array(false, $aSuccess)){
			throw new Exception('Backup Error!');
		}
		
		// Spalte "update_time" in die Tabelle der Wechselkurs-Tabellen hinzufügen
		$bAddField = $this->_addColumn('tc_exchangerates_tables', 'update_time', 'name');
		
		if(!$bAddField) {
			throw new Exception ('Unable to add column "update_time"');
		}
		
		WDCache::flush();
		DB::begin('convert_exchangerate_update_time');
		
		// alle Wechselkurs-Tabellen holen
		$aExchangeRates = $this->_getAllExchangeRateTables();
		
		$aSuccess = array();
		try {
			
			// Wechselkurs-Tabellen durchlaufen und update_time setzen
			foreach($aExchangeRates as $iKey => $aData) {
				
				$iId = (int) $aData['id'];
				$oExchangerate = Ext_TC_Exchangerate_Table::getInstance($iId);

				$aSources = $oExchangerate->getSources();
				$oSource = reset($aSources);

				$oExchangerate->update_time = (int) $oSource->update_time;

				$oExchangerate->save();

			}
		
			// Spalte "update_time" aus der Tabelle der Wechselkurs-Quellen löschen
			$bDropColumn = $this->_dropColumn('tc_exchangerates_tables_sources', array('update_time'));

			if($bDropColumn !== false) {
				DB::commit('convert_exchangerate_update_time');
				WDCache::flush();
				return true;
			} else {
				DB::rollback('convert_exchangerate_update_time');
			}
			
		} catch (Exception $e) {
			DB::rollback('convert_exchangerate_update_time');
			__pout($e);
		}	

		
		return false;
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

		$bSuccess = DB::addField($sTable, $sColumn, 'TINYINT( 2 ) NOT NULL', $sAfter);
		
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
	
}