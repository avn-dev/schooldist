<?php

namespace Office\Service;

class Timeclock {
	
	public function execute() {
		
		/**
		 * Timeclock adjustments
		 */
		$iHour = date('G');
		$oDate = new \WDDate();
		if($iHour < 23) {
			$oDate->sub(1, \WDDate::DAY);
		}
		\Ext_Office_Timeclock::executeChecks($oDate->get(\WDDate::DB_DATE));
		
	}
	
}
