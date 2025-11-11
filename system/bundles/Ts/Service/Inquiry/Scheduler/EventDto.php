<?php

namespace Ts\Service\Inquiry\Scheduler;

use Carbon\Carbon;

class EventDto
{
	public function __construct(
		public string $type,
		public mixed $id,
		public Carbon $start,
		public Carbon $end,
		public string $title,
		public string $location = '',
		public array $additional = []
	) {}

	public function additional(string $key, $value): static
	{
		$this->additional[$key] = $value;
		return $this;
	}

	public function getAdditional(string $key, $default = null): mixed
	{
		return $this->additional[$key] ?? $default;
	}

	public function __get(string $key)
	{
		return $this->getAdditional($key);
	}

	public function __set(string $key, $value): void
	{
		$this->additional($key, $value);
	}

}