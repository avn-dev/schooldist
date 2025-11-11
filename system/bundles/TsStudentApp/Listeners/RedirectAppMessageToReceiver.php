<?php

namespace TsStudentApp\Listeners;

use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Traits\Events\ManageableTrait;
use TsStudentApp\Events\AppMessageReceived;

class RedirectAppMessageToReceiver implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Nachricht an Empfänger weiterleiten');
	}

	public function handle(AppMessageReceived $payload)
	{
		$payload->getReceiver()->notify($payload->getNotification($this));
	}

}