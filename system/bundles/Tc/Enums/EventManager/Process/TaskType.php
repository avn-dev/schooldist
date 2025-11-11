<?php

namespace Tc\Enums\EventManager\Process;

enum TaskType
{
	case LISTENER;

	case CONDITION;

	public function isListener(): bool
	{
		return $this === self::LISTENER;
	}

	public function isCondition(): bool
	{
		return $this === self::CONDITION;
	}
}
