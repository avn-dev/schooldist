<?php

/**
 * Die Tabellenstruktur für die Flex Tabelle ändern, wo die Daten gespeichert werden
 * 
 * @author Mehmet Durmaz
 */
class Ext_TS_System_Checks_Flex_FieldValueStructure extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$aColumns = DB::describeTable('tc_flex_sections_fields_values', true);
		
		if(!isset($aColumns['id']))
		{
			// Wenn vorher erfolgreich die Struktur umgestellt wurde, check nicht erneut ausführen
			return true;
		}
		
		// Backup anlegen
		$mSuccess = Util::backupTable('tc_flex_sections_fields_values');
		
		if(!$mSuccess)
		{
			__pout("Failed to create backup!"); 

			return false;
		}
		
		// Datensätze säubern
		$bSuccess = $this->_cleanData();
		
		if(!$bSuccess)
		{
			__pout("Failed to clean!"); 

			return false;
		}
		
		// ID und active Feld entfernen
		$sSql = "
			ALTER TABLE 
				`tc_flex_sections_fields_values` 
			DROP `id` , DROP `active`
		";
		
		$rRes = DB::executeQuery($sSql);
		
		if(!$rRes)
		{
			__pout("Failed to drop columns!"); 

			return false;
		}
		
		// Neuen Primary auf field_id und item_id setzen
		$sSql = "
			ALTER TABLE 
				`tc_flex_sections_fields_values` 
			ADD PRIMARY KEY ( `field_id` , `item_id` )
		";
		
		$rRes = DB::executeQuery($sSql);
		
		if(!$rRes)
		{
			__pout("Failed to add primary!"); 

			return false;
		}
		
		return true;
	}
	
	/**
	 * Damit der primary auf field_id,item_id nicht fehlschlägt suchen wir in dieser Methode
	 * auf diese 2 Felder doppelte Einträge, außerdem suchen wir auch nach inaktiven Einträgen
	 * die müssen auch gelöscht werden, da es später kein active feld mehr geben wird...
	 *  
	 * @return mixed 
	 */
	protected function _cleanData()
	{
		$sSql = "
			SELECT 
				`t1`.`id`
			FROM 
				`tc_flex_sections_fields_values` `t1`
			WHERE 
				`t1`.`id` != (
						SELECT 
							`t2`.`id`
						FROM 
							`tc_flex_sections_fields_values` `t2`
						WHERE 
							`t2`.`field_id` = `t1`.`field_id` AND 
							`t2`.`item_id` = `t1`.`item_id` AND 
							`t2`.`active` = 1 
						ORDER BY
							`changed` DESC
						LIMIT
							1
					)
		";
		
		// Alle doppelten und inaktiven Einträge
		$aIds = (array)DB::getQueryCol($sSql);
		
		if(empty($aIds))
		{
			return true;
		}
		
		$bSuccess = true;
		
		// Datensätze löschen
		foreach($aIds as $iId)
		{
			$sSql = "
				DELETE FROM
					`tc_flex_sections_fields_values`
				WHERE
					`id` = :id
			";
			
			$aSql = array(
				'id' => $iId
			);
			
			$rRes = DB::executePreparedQuery($sSql, $aSql);
			
			if(!$rRes)
			{
				$bSuccess = false;
			}
		}
		
		return $bSuccess;
	}
	
	public function getTitle()
	{
		return 'Change Flex Sturcture';
	}
	
	public function getDescription()
	{
		return 'Change the structure for saved flexibility entries';
	}
}