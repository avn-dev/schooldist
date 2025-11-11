<?php

namespace Cms\Hook;

use Core\Console\Scheduler;

class SchedulerHook extends \Core\Service\Hook\AbstractHook {
	
	public function run(Scheduler $oScheduler) {

		$oScheduler->command(\Cms\Command\GenerateStats::class)
			->name('CMS Statistics')
			->everyFifteenMinutes();

	}
	
}


