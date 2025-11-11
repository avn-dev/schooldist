<?php

class Ext_TS_System_Checks_System_Color extends GlobalChecks {
	
	public function getTitle() {
		return 'System colors';
	}
	
	public function getDescription() {
		return 'Prepares database for system colors';
	}
	
	public function executeCheck() {
		
		$bBackup = Ext_Thebing_Util::backupTable('kolumbus_clients');
		$bBackup2 = Ext_Thebing_Util::backupTable('customer_db_2');
		
		if(!$bBackup || !$bBackup2) {
			__pout('backup error!');
			return false;
		}
		
		$oDB = DB::getDefaultConnection();
		
		if(!$oDB->checkField('kolumbus_clients', 'system_color')) {					
			$sSql = "ALTER TABLE `kolumbus_clients` ADD `system_color` VARCHAR( 7 ) NOT NULL";
			$oDB->query($sSql);	
			$this->_setDefaultColor('kolumbus_clients');
		}
		
		if(!$oDB->checkField('customer_db_2', 'system_color')) {					
			$sSql = "ALTER TABLE `customer_db_2` ADD `system_color` VARCHAR( 7 ) NOT NULL ";
			$oDB->query($sSql);
			$this->_setDefaultColor('customer_db_2');
		}
		
		WDCache::flush();
			
		return true;
	}
	
	/**
	 * Setzt die Defaultfarbe in eine Tabelle bei der noch keine Farbe eingetragen wurde
	 * 
	 * @param string $sTable
	 */
	protected function _setDefaultColor($sTable) {
		$aData = array('system_color' => '#0a50a1');		
		DB::updateData($sTable, $aData, ' `system_color` = "" ');		
	}
	
}