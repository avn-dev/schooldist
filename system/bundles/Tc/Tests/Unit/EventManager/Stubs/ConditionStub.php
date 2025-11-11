<?php

namespace Tc\Tests\Unit\EventManager\Stubs;

use Tc\Interfaces\EventManager\Manageable;
use Tc\Traits\Events\ManageableTrait;

class ConditionStub implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return __METHOD__;
	}

	public function passes($payload) {
		return true;
	}
}