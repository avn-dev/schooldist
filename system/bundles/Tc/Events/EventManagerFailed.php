<?php

namespace Tc\Events;

use Core\Interfaces\Events\SystemEvent;
use Tc\Exception\EventManagerException;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Illuminate\Foundation\Events\Dispatchable;

class EventManagerFailed implements ManageableEvent, SystemEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication;

	public function __construct(private EventManagerException $ex) {}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Ereignissteuerung fehlgeschlagen');
	}

	public function getException(): EventManagerException
	{
		return $this->ex;
	}

	public function getSystemUserNotification($listener, $notification, $users)
	{
		if (!$notification) {
			$notification = (new \Tc\Notifications\SystemUserNotification(''));
		}

		$notification->group(EventManager::l10n()->translate('Systembenachrichtigung'));
		$notification->message(sprintf(
			EventManager::l10n()->translate('Bei der Ausführung des Events <b>%s</b> ist ein Fehler aufgetreten:<br/><br/>%s<br/><br/>%s'),
			$this->ex->getProcess()->getHumanReadableText(EventManager::l10n()),
			$this->ex->getErrorMessage(),
			(string)$this->ex->getPrevious()?->getTraceAsString()
		));

		return [$notification, $users];
	}

}