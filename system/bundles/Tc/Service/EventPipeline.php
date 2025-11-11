<?php

namespace Tc\Service;

use Tc\Exception\EventPipelineException;

class EventPipeline {

	private string|object|null $payload = null;

	private array $listeners = [];

	private array $conditions = [];

	private ?\Closure $finally;

	private static bool $debugEnabled = false;

	private static array $debug = [];

	public function send($payload): static
	{
		$this->payload = $payload;
		return $this;
	}

	public function through(array $listeners): static
	{
		$this->listeners = array_merge($this->listeners, $listeners);
		return $this;
	}

	public function if(array $conditions): static
	{
		$this->conditions = array_merge($this->conditions, $conditions);
		return $this;
	}

	public function finally(\Closure $callback): static
	{
		$this->finally = $callback;
		return $this;
	}

	public function run(): mixed
	{
		if ($this->payload === null) {
			throw new \RuntimeException('No payload given for event pipeline');
		}

		if (empty($this->listeners)) {
			throw new \RuntimeException('No listeners given for event pipeline');
		}

		$globalStart = microtime(true);

		$debugIndex = count(self::$debug[$this->getEventName()] ?? []);

		// Alles auf false setzen, noch nicht erfolgreich durchgelaufen
		$this->debug(fn () => self::$debug[$this->getEventName()][$debugIndex]['conditions'] = array_map(fn ($condition) => ['object' => $condition[0], 'success' => false], $this->conditions));
		$this->debug(fn () => self::$debug[$this->getEventName()][$debugIndex]['listeners'] = array_map(fn ($listener) => ['object' => $listener[0], 'success' => false], $this->listeners));

		// [$object, 'method']
		foreach ($this->conditions as $index => $condition) {
			try {
				$start = microtime(true);

				$passes = $condition($this->payload);

				$this->debug(fn () => self::$debug[$this->getEventName()][$debugIndex]['conditions'][$index]['time'] = microtime(true) - $start);
				$this->debug(fn () => self::$debug[$this->getEventName()][$debugIndex]['conditions'][$index]['success'] = $passes);

				if ($passes === false) {
					return false;
				}
			} catch (\Throwable $throwable) {
				$this->debug(fn () => self::$debug[$this->getEventName()][$debugIndex]['conditions'][$index]['error'] = $throwable->getMessage());
				throw new EventPipelineException($condition[0], $throwable, sprintf('Condition failed [%s]', $condition[0]::class));
			}
		}

		// [$object, 'method']
		foreach ($this->listeners as $index => $listener) {
			try {
				$start = microtime(true);

				$listener($this->payload);

				$this->debug(fn () => self::$debug[$this->getEventName()][$debugIndex]['listeners'][$index]['time'] = microtime(true) - $start);
				$this->debug(fn () => self::$debug[$this->getEventName()][$debugIndex]['listeners'][$index]['success'] = true);
			} catch (\Throwable $throwable) {
				$this->debug(fn () => self::$debug[$this->getEventName()][$debugIndex]['listeners'][$index]['error'] = $throwable->getMessage());
				// TODO andere Listener trotzdem durchlaufen auch wenn ein Listener fehlgeschlagen ist?
				throw new EventPipelineException($listener[0], $throwable, sprintf('Listener failed [%s]', $listener[0]::class));
			}
		}

		if ($this->finally !== null) {
			$callback = $this->finally;
			$callback($this);
		}

		$this->debug(fn () => self::$debug[$this->getEventName()][$debugIndex]['time'] = microtime(true) - $globalStart);

		return $this->payload;
	}

	private function getEventName(): ?string
	{
		if (is_object($this->payload)) {
			return $this->payload::class;
		}

		return $this->payload;
	}

	private function debug(\Closure $callback): void
	{
		if (self::$debugEnabled) {
			$callback();
		}
	}

	public static function enableDebug(): void
	{
		self::$debugEnabled = true;
	}

	public static function getDebug(string $eventName = null): array
	{
		if ($eventName !== null) {
			return self::$debug[$eventName] ?? [];
		}

		return self::$debug;
	}

	public static function resetDebug(string $eventName = null): void
	{
		if ($eventName !== null) {
			unset(self::$debug[$eventName]);
		}

		self::$debug = [];
	}

}
