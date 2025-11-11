<?php

namespace Tc\Traits\Events\Manageable;

use Tc\Listeners;

trait WithManageableSystemUserCommunication
{
	public static function manageSystemUserCommunication(): void
	{
		self::addManageableListener(Listeners\SendSystemUserNotification::class);
		// TODO self::addManageableListener(Listeners\CreateUserTask::class);
	}

	/**
	 * Hauptsächlich für den Platzhalter-Tab im Dialog
	 *
	 * @param self|null $event
	 * @return \Ext_TC_Placeholder_Abstract|null
	 */
	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		return null;
	}
}
