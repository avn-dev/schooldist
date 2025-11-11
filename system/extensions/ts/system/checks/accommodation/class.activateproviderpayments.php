<?php

class Ext_TS_System_Checks_Accommodation_ActivateProviderPayments extends GlobalChecks {

	public function getTitle() {
		return 'Update accommodation provider payment data';
	}

	public function getDescription() {
		return 'Activates new accommodation provider payments.';
	}

	/**
	 * Bezahlte Zuweisungen als bezahlt markieren
	 * @return boolean
	 */
	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '1G');

		$aBackupTables = array(
			'kolumbus_accommodations_allocations'
		);
		
		foreach($aBackupTables as $sBackupTable) {
			$bBackup = Util::backupTable($sBackupTable);
			if(!$bBackup) {
				return false;
			}
		}

		
		
		// Neue Unterkunftsbezahlung aktivieren
		System::s('new_accommodation_provider_payments_activated', 1);		

		return true;		
	}
	
}