<?php

namespace Tc\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\Events\EntityDispatcher;
use Tc\Facades\EventManager;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;

class EntityEventDispatched implements ManageableEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication;

	public function __construct(protected EntityDispatcher $event) {}

	public function getEntity(): \WDBasic
	{
		return $this->event->getEntity();
	}

	public function getEvent(): EntityDispatcher
	{
		return $this->event;
	}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Entität beobachten');
	}

}
