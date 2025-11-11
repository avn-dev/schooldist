<?php

namespace TsTuition\Hook;

use Core\Console\Scheduler;
use TsTuition\Service\CourseRenewalService;

class SchedulerHook extends \Core\Service\Hook\AbstractHook
{
	public function run(Scheduler $scheduler)
	{
		$scheduler->call(fn() => (new CourseRenewalService())->generateTasks())
			->name('Automatic Course Renewal')
			->when(fn() => \TcExternalApps\Service\AppService::hasApp(\TsTuition\Handler\CourseRenewalApp::APP_NAME))
			->daily();
	}
}
