<?php

namespace Core\Service;

use Core\Service\Events\EventCollection;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Events\Dispatcher;

class EventTransaction
{
	/**
	 * @var Dispatcher[]
	 */
	private array $originalDispatchers = [];
	/**
	 * @var EventCollection[]
	 */
	private array $collections = [];

	public function __construct(private ContainerContract $container) {}

	public function begin(string $name, array|string $eventNames = [])
	{
		$originalDispatcher = $this->container->make('events');

		$this->container->instance('events', new EventCollection($this->container, $eventNames));
		$this->originalDispatchers[$name] = $originalDispatcher;
	}

	public function stop(string $name)
	{
		if (!isset($this->originalDispatchers[$name])) {
			throw new \RuntimeException(sprintf('Unknown event transaction [%s]', $name));
		}

		/* @var EventCollection $collection */
		$collection = $this->container->make('events');

		$this->container->instance('events', $this->originalDispatchers[$name]);
		unset($this->originalDispatchers[$name]);

		$this->collections[$name] = $collection;

		return $collection->getDispatchedEvents();
	}

	public function commit(string $name, array $events = [])
	{
		if (isset($this->originalDispatchers[$name])) {
			$this->stop($name);
		}

		if (!isset($this->collections[$name])) {
			return null;
		}

		return (isset($this->collections[$name]))
			? $this->collections[$name]->run($events)
			: [];
	}
}