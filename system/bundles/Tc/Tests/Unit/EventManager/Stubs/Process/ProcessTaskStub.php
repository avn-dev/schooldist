<?php

namespace Tc\Tests\Unit\EventManager\Stubs\Process;

use Tc\Enums\EventManager\Process\TaskType;
use Tc\Interfaces\EventManager\Process\Task;

class ProcessTaskStub implements Task
{

	public function __construct(private readonly TaskType $type, private readonly string $class) {}

	public function getIdentifier(): string|int
	{
		return static::class;
	}

	public function getType(): TaskType
	{
		return $this->type;
	}

	public function getClass(): string
	{
		return $this->class;
	}

	public function getSettings(): array
	{
		return [];
	}

	public function getSetting(string $key, $default = null)
	{
		return $default;
	}

}