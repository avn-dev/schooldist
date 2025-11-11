<?php

namespace Admin\Hooks;

use Core\Console\Scheduler;

class SchedulerHook extends \Core\Service\Hook\AbstractHook {

	public function run(Scheduler $oScheduler) {

		$oScheduler->call(function() {
			\Admin\Service\ChecksReminder::run();
		})->everyFifteenMinutes();

	}

}