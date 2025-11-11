<?php

namespace TsEdvisor\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
	protected $listen = [
		\Ts\Events\Inquiry\ConfirmEvent::class => [
			\TsEdvisor\Listeners\ConfirmInquiry::class
		]
	];
}
