<?php

namespace Tc\Tests\Unit\EventManager\Stubs\Process;

use Illuminate\Support\Collection;
use Tc\Interfaces\EventManager\Process;
use Tc\Tests\Unit\EventManager\Stubs\EventStub;

/**
 * TODO Settings
 */
class ProcessStub implements Process
{
	public function __construct(
		private array $listeners,
		private array $conditions,
		private array $settings = []
	) {}

	public function getIdentifier(): string|int
	{
		return 'test';
	}

	public function getHumanReadableText($l10n): string
	{
		return 'Process stub';
	}

	public function getProcessName(): string
	{
		return EventStub::class;
	}

	public function getListeners(): Collection
	{
		return collect($this->listeners);
	}

	public function getConditions(): Collection
	{
		return collect($this->conditions);
	}

	public function updateLastAction(): bool
	{
		return true;
	}

	public function getSettings(): array
	{
		return [];
	}

	public function getSetting(string $key, $default = null)
	{
		return $this->settings[$key] ?? $default;
	}
}