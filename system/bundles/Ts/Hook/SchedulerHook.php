<?php

namespace Ts\Hook;

use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Core\Console\Scheduler;
use OpenBanking\Providers\finAPI\ExternalApp as finApi;
use Tc\Facades\EventManager;
use TcExternalApps\Service\AppService;

class SchedulerHook extends \Core\Service\Hook\AbstractHook
{
	public function run(Scheduler $scheduler)
	{
		/**
		 * Event-Manager
		 */
		$scheduler->call(function () {
			$now = new Carbon();
			\Ext_Thebing_School::query()->each(function (\Ext_Thebing_School $school) use ($now) {
				$timezone = $school->getTimezone();
				$schoolNow = clone $now;
				$schoolNow->setTimezone(new CarbonTimeZone($timezone));

				EventManager::handleScheduled($schoolNow, $school);
			});
		})
		->name('Eventmanager')
		->hourly();

		/**
		 * Systembenachrichtigungen
		 */
		$scheduler->call(function () {
			\Tc\Service\SystemEvents::dispatchNewsEvents();
			\Core\Service\SystemEvents::dispatchSystemUpdates();
		})
		->name('System Notifications')
		->hourly();

		/**
		 * Open Banking
		 */
		$scheduler->call(function () {
			\Ts\Service\OpenBanking\IncomingPayments::run();
		})
		->name('Open Banking')
		->hourlyAt(15);

		/**
		 * finAPI
		 */
		if (AppService::hasApp(finApi::APP_KEY)) {
			$scheduler->command('open-banking:finapi:update')
				->name('finAPI - Background update')
				->hourly();
		}
	}
}
