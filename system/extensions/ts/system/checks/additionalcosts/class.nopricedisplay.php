<?php

class Ext_TS_System_Checks_AdditionalCosts_NoPriceDisplay extends GlobalChecks {
	
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
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$bBackup = Ext_Thebing_Util::backupTable('kolumbus_costs');
		
		if(!$bBackup) {
			return false;
		}
		
		$oTempAdditionalCost = new Ext_Thebing_School_Additionalcost();
		
		$aList = $oTempAdditionalCost->getArrayList();
		
		foreach($aList as $aEntry) {			
			if(
				$aEntry['type'] != 2 &&
				$aEntry['no_price_display'] == 0
			) {
				$iAdditionalCost = (int) $aEntry['id'];
				$oAdditionalCost = Ext_Thebing_School_Additionalcost::getInstance($iAdditionalCost);

				$oAdditionalCost->no_price_display = 1;

				$oAdditionalCost->save();			
			}			
		}
		
		return true;
	}

}
