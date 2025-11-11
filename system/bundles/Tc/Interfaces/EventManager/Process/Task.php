<?php

namespace Tc\Interfaces\EventManager\Process;

use Tc\Interfaces\Events\Settings;
use Tc\Enums\EventManager\Process\TaskType;

interface Task extends Settings
{
	public function getIdentifier(): string|int;

	public function getType(): TaskType;

	public function getClass(): string;
}