<?php

class Ext_TC_System_Checks_Exchangerates_Factor extends GlobalChecks {

	public function getTitle()
	{
		$sTitle = 'Exchange rate factor';
		return $sTitle;
	}

	public function getDescription() 
	{
		$sDescription = 'Prepare the database for exchange rates factor';
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
		
		$bBackUp = Util::backupTable('tc_exchangerates_tables_rates');
		if(!$bBackUp) {
			__pout('backup error!');
			return false;
		}
		
		DB::begin('Ext_TC_System_Checks_Exchangerates_Database');
		
		try {
		
			$sSql = "
				UPDATE 
					`tc_exchangerates_tables_rates`
				SET
					`rate` = `price`
				WHERE
					`factor` = 0
			";

			DB::executeQuery($sSql);
		
		} catch (Exception $e) {
			DB::rollback('Ext_TC_System_Checks_Exchangerates_Database');
			__pout($e);
			return false;
		}
		
		DB::commit('Ext_TC_System_Checks_Exchangerates_Database');
		
		return true;		
	}
	
}