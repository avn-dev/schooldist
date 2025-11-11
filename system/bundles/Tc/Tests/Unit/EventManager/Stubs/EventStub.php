<?php

namespace Tc\Tests\Unit\EventManager\Stubs;

use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\ManageableTrait;

class EventStub implements ManageableEvent
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return __METHOD__;
	}

	public static function getManageableListeners(): array
	{
		return [ListenerStub::class];
	}

	public static function getManageableConditions(): array
	{
		return [ConditionStub::class];
	}

}