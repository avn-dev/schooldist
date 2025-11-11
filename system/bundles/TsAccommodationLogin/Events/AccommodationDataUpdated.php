<?php

namespace TsAccommodationLogin\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;

class AccommodationDataUpdated implements ManageableEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication;

	public function __construct(
		private readonly \Ext_Thebing_Accommodation $accommodation
	) {}

	public function getAccommodation(): \Ext_Thebing_Accommodation
	{
		return $this->accommodation;
	}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Unterkunftsanbieter hat seine Daten aktualisiert');
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$accommodation = ($event) ? $event->getAccommodation() : new \Ext_Thebing_Accommodation();
		return $accommodation->getPlaceholderObject();
	}

}