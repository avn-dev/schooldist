<?php

class Ext_TS_System_Checks_AdditionalCosts_CalculationTimepoint extends GlobalChecks {
	
	public function getDescription() 
	{
		return 'Add default settings for additional costs';
	}
	
	public function getTitle()
	{
		return 'Additional Costs'; 
	}
	
	public function executeCheck()
	{
		$bBackup = Ext_Thebing_Util::backupTable('kolumbus_costs');
		
		if(!$bBackup) {
			return false;
		}

		$sSql = "
			UPDATE
				`kolumbus_costs`
			SET
				`timepoint` = 1
			WHERE
				`timepoint` = 0
		";

		DB::executeQuery($sSql);

		return true;
	}

}
