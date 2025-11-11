<?php

class Ext_TS_System_Checks_Prices_TruncatePackage extends GlobalChecks {
	
	public function getTitle() {
		return 'Package Price Checkboxes';
	}
	
	public function getDescription() {
		return 'Deactivates package price checkboxes';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		$bBackup = Ext_Thebing_Util::backupTable('kolumbus_prices_paket');
		
		if(!$bBackup) {
			__pout('backup error!');
			return false;
		}
		
		$sSql = "TRUNCATE TABLE `kolumbus_prices_paket`";
		
		DB::executeQuery($sSql);
		
		return true;

	}

}