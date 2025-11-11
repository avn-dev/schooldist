<?php

namespace Core\Providers;

use Core\Events\ParallelProcessing\TaskFailed;
use Core\Listeners\ReportError;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;


class EventServiceProvider extends ServiceProvider {

	/**
	 * The event handler mappings for the application.
	 * https://laravel.com/docs/9.x/events#registering-events-and-listeners
	 *
	 * @var array
	 */
	protected $listen = [
		TaskFailed::class => [
			ReportError::class
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
	protected $observers = [];

}
