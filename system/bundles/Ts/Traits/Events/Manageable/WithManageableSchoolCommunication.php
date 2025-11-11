<?php

namespace Ts\Traits\Events\Manageable;

use Ts\Events\Conditions\SchoolCondition;
use Ts\Listeners;

trait WithManageableSchoolCommunication
{
	public static function manageSchoolCommunication(): void
	{
		// Listeners
		self::addManageableListener(Listeners\SendSchoolNotification::class);
		// Conditions
		// TODO nicht standardmäßig inkludieren
		self::addManageableCondition(SchoolCondition::class);
	}

}
