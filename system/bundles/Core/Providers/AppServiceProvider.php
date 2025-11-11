<?php

namespace Core\Providers;

use Admin\Instance as Admin;
use Admin\Notifications\Channels\AdminMailChannel;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\Repository;

class AppServiceProvider extends ServiceProvider
{
	public function boot()
	{
		// Event-Dispatcher für Model-Events setzen (https://laravel.com/docs/10.x/eloquent#observers)
		\WDBasic::setEventDispatcher($this->app['events']);
		// Notification Channels ('database' wird bereits von Laravel nativ registriert)
		Notification::extend('admin-mail', fn ($app) => new AdminMailChannel());
		
		Cache::extend('FideloMemcached', function ($app) {

            $store = new \Core\Service\Cache\LaravelStore();

            return new Repository($store);
        });
		
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		if (!$this->app->providerIsLoaded(FoundationServiceProvider::class)) {
			// Output von dd() um nützliche Informationen erweitern (z.B. Pfad)
			$this->app->resolveProvider(FoundationServiceProvider::class)->registerDumper();
		}

		$this->app->singleton(\Illuminate\Contracts\Debug\ExceptionHandler::class, \Illuminate\Foundation\Exceptions\Handler::class);
		// Wichtig für Scheduler
		$this->app->singleton(\Illuminate\Contracts\Foundation\MaintenanceMode::class, \Core\App\MaintenanceMode::class);
		// Laravel Database-Channel überschreiben da wir durch die WDBasic eine eigene Klasse brauchen
		$this->app->bind(\Illuminate\Notifications\Channels\DatabaseChannel::class, \Core\Notifications\Channels\DatabaseChannel::class);
		// Smarty-Template-Engine hinzufügen
		$this->app->resolving('view', fn (ViewFactory $view) => $view->addExtension('tpl', 'smarty', fn() => new \Core\View\Smarty));
		// TODO in eigenen service provider auslagern
		$this->app->singleton(Admin::class);

		if ($this->app->runningInConsole()) {
			$this->app->bind(\Illuminate\Contracts\Cache\Factory::class, fn () => new \Core\Console\Scheduling\CacheFactory());
		}

		config([
			'cache.default' => 'FideloMemcached',
			'cache.stores.FideloMemcached' => [
				'driver' => 'FideloMemcached',
			],
		]);
		
	}

}
