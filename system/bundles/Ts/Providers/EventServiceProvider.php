<?php

namespace Ts\Providers;

use Tc\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {

	/**
	 * The event handler mappings for the application.
	 * https://laravel.com/docs/9.x/events#registering-events-and-listeners
	 *
	 * @var array
	 */
	protected $listen = [
		\Communication\Events\MessagesSent::class => [
			\Ts\Communication\Listeners\RefreshIndexes::class
		]
	];

	/**
	 * The subscribers to register.
	 * https://laravel.com/docs/9.x/events#writing-event-subscribers
	 *
	 * @var array
	 */
	protected $subscribe = [];

	/**
	 * The model observers to register.
	 * https://laravel.com/docs/9.x/eloquent#observers
	 *
	 * @var array
	 */
	protected $observers = [
		\Ext_TS_Inquiry::class => [
			\Ts\Observers\InquiryObserver::class
		]
	];

	/*public function boot()
	{
		parent::boot();
		// EventManager::enableLogging();
		// EventManager::turnOff();
	}*/

}
