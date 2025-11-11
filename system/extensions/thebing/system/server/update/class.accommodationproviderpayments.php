<?php

use Ts\Handler\AccommodationProvider\PaymentHandler;

class Ext_Thebing_System_Server_Update_AccommodationProviderPayments extends Ext_Thebing_System_Server_Update {
	
	protected $sExecutionTimeField = 'execution_time_accommodation_provider_payments';

	public $bIgnoreExecutionError = true;

	public function executeUpdate() {

		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		if(System::d('new_accommodation_provider_payments_activated')) {

			self::log('Accommodation provider payments: start');

			$bBackup = true;
			$aSchools = Ext_Thebing_Client::getSchoolList(false, 0, true);
			foreach($aSchools as $oSchool) {
				$oPaymentHandler = new PaymentHandler($oSchool);
				$oPaymentHandler->bCheckBackup = $bBackup;
				$oPaymentHandler->resetPendingPayments();
				$oPaymentHandler->generate();
				$bBackup = false;
			}

			self::log('Accommodation provider payments: end');
			
		}

	}
	
}