<?php

namespace Tc\Providers;

use Core\Facade\Cache;
use Tc\Interfaces\EventManager\Repository;
use Tc\Service\EventManager\ModelRepository;
use Tc\Facades\EventManager;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

abstract class EventServiceProvider extends ServiceProvider
{
	public function boot()
	{
		parent::boot();

		$this->app->afterResolving(\Tc\Service\EventManager::class, function (\Tc\Service\EventManager $eventManager) {
			$eventManagerEvents = $this->getConfiguredEvents()['listen'] ?? [];

			foreach ($eventManagerEvents as $event) {
				if (is_array($event)) {
					$eventManager->listen($event[0], $event[1]);
				} else {
					$eventManager->listen($event);
				}
			}
		});

		// Event-Manager (Interface-Tasks) auf alle Events reagieren lassen
		Event::listen('*', fn ($eventName, $payload) => EventManager::handle($eventName, $payload));
	}

	public function register()
	{
		parent::register();

		// Prozesse des EventManagers kommen aus der Datenbank
		$this->app->bind(Repository::class, ModelRepository::class);
		// Eventmanager
		$this->app->singleton(\Tc\Service\EventManager::class, function ($app) {
			return  new \Tc\Service\EventManager($app, $app->make(Repository::class));
		});
	}

	private function getConfiguredEvents(): array
	{
		return Cache::remember('tc_event_management_config', 60*60*24, function () {

			$files = (new \Core\Helper\Config\FileCollector())->collectAllFileParts();
			$config = [];

			foreach ($files as $file) {
				if (!empty($fileConfig = $file->get('event_manager', []))) {
					$config = array_merge_recursive($config, $fileConfig);
				}
			}

			return $config;
		});
	}

}
