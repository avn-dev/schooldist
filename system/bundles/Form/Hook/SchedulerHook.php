<?php

namespace Form\Hook;

use Core\Console\Scheduler;

class SchedulerHook extends \Core\Service\Hook\AbstractHook {
	
	public function run(Scheduler $oScheduler) {
		
		$oScheduler->command(\Form\Command\MailSend::class)
				->everyMinute()
				->withoutOverlapping();
		
	}
	
}


