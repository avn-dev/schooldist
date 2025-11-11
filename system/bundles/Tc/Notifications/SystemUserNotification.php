<?php

namespace Tc\Notifications;

use Core\Notifications\SystemUserNotification as BaseSystemUserNotification;
use Tc\Interfaces\EventManager\ManageableNotification;
use Tc\Traits\Events\ManageableNotificationTrait;

class SystemUserNotification extends BaseSystemUserNotification implements ManageableNotification
{
	use ManageableNotificationTrait;

	public function toArray()
	{
		$array = parent::toArray();

		if ($this->isManaged()) {
			$array['process'] = ['class' => $this->process::class, 'id' => $this->process->getIdentifier()];
			$array['task'] = ['class' => $this->task::class, 'id' => $this->task->getIdentifier()];
		}

		return $array;
	}
}