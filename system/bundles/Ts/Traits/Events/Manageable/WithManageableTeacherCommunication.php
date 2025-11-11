<?php

namespace Ts\Traits\Events\Manageable;

use Ts\Listeners;
use Ts\Events\Inquiry\Conditions;

trait WithManageableTeacherCommunication
{
	/**
	 * Wird automatisch eingelesen
	 *
	 * @return void
	 */
	public static function manageTeacherCommunicationCommunication(): void
	{
		self::addManageableListener(Listeners\SendTeacherNotification::class);
	}

}
