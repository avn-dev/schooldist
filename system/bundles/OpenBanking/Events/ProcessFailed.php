<?php

namespace OpenBanking\Events;

use Core\Interfaces\Events\SystemEvent;
use Illuminate\Foundation\Events\Dispatchable;
use OpenBanking\Exception\ProcessException;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;

class ProcessFailed implements ManageableEvent, SystemEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Open Banking: Prozess fehlgeschlagen');
	}

	public function __construct(
		private readonly ProcessException $ex
	) {}

	public function getSystemUserNotification($listener, $notification, $users)
	{
		if (!$notification) {
			$notification = (new \Tc\Notifications\SystemUserNotification(''));
		}

		$notification->group(EventManager::l10n()->translate('Systembenachrichtigung'));

		$process = $this->ex->getProcess();
		$task = $this->ex->getTask();
		$exception = (!empty($previous = $this->ex->getPrevious()))
			? $previous
			: $this->ex;

		if ($process) {
			if ($task) {
				$message = sprintf(
					EventManager::l10n()->translate('Bei der Ausführung des Open Banking-Prozesses ist ein Fehler bei "%s" aufgetreten:<br/><br/>%s'),
					$task->getHumanReadableText(EventManager::l10n()),
					$exception->getMessage()
				);
			} else {
				$message = sprintf(
					EventManager::l10n()->translate('Bei der Ausführung des Open Banking-Prozesses "%s" ist ein Fehler aufgetreten:<br/><br/>%s'),
					$process->getHumanReadableText(EventManager::l10n()),
					$exception->getMessage()
				);
			}
		} else {
			$message = sprintf(
				EventManager::l10n()->translate('Bei der Ausführung eines Open Banking-Prozesses ist ein Fehler aufgetreten:<br/><br/>%s'),
				$exception->getMessage()
			);
		}

		$notification->message($message);

		return [$notification, $users];
	}
}