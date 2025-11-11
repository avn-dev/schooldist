<?php

namespace Tc\Interfaces\EventManager;

use Core\Interfaces\HumanReadable;
use Tc\Interfaces\Events\Settings;
use Illuminate\Support\Collection;

interface Process extends Settings, HumanReadable
{
	public function getIdentifier(): string|int;

	public function getProcessName(): string;

	public function getListeners(): Collection;

	public function getConditions(): Collection;

	public function updateLastAction(): bool;
}