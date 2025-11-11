<?php

namespace Ts\Traits\Events\Manageable;

use Ts\Listeners;

trait WithManageableIndividualCommunication
{
	public static function manageIndividualCommunication(): void
	{
		// Listeners
		self::addManageableListener(Listeners\SendIndividualEmail::class);
	}

}
