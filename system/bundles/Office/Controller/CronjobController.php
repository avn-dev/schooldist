<?php

namespace Office\Controller;

class CronjobController {
	
	public function request() {

		$oLog = \Log::getLogger('office');

		$oLog->addInfo('Cronjob start');

		$oTimeClockService = new \Office\Service\Timeclock;
		$oTimeClockService->execute();
		
		$oReminderService = new \Office\Service\Reminder;
		$oReminderService->execute();
		
		$oLog->addInfo('Cronjob end');
		
	}
	
}
