<?php

namespace Tc\Tests\Unit\EventManager\Stubs;

use Tc\Interfaces\EventManager\Manageable;
use Tc\Traits\Events\ManageableTrait;

class ListenerStub implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return __METHOD__;
	}

	public function handle($payload) {
		return true;
	}

}