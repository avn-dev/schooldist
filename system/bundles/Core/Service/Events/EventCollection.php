<?php

namespace Core\Service\Events;

use Illuminate\Contracts\Container\Container as ContainerContract;

class EventCollection extends \Illuminate\Events\Dispatcher
{
	private array $events = [];

	public function __construct(
		ContainerContract $container = null,
		private array $eventNames = [],
	) {
		parent::__construct($container);
	}

	public function dispatch($event, $payload = [], $halt = false)
	{
		$eventName = (is_object($event)) ? $event::class : $event;

		if (!empty($this->eventNames) && !in_array($eventName, $this->eventNames)) {
			return parent::dispatch($event, $payload, $halt);
		}

		$this->events[] = [$event, $payload, $halt];

		return null;
	}

	public function getDispatchedEvents(): array
	{
		return $this->events;
	}

	public function run($dispatchedEvents = [])
	{
		if (empty($dispatchedEvents)) {
			$dispatchedEvents = $this->events;
		}

		$responses = array_map(fn ($event) =>  $this->container['events']->dispatch($event[0], $event[1], $event[2]), $dispatchedEvents);

		return $responses;
	}
}