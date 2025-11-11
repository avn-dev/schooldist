<?php

namespace Tc\Events\Conditions;

use Core\Interfaces\Events\SystemEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Traits\Events\ManageableTrait;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;

class NewsType implements Manageable, SystemEvent
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		// TODO: Implement getTitle() method.
	}
}