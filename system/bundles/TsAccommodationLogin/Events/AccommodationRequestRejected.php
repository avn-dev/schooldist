<?php

namespace TsAccommodationLogin\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\Events\AccommodationEvent;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Interfaces\Events\SchoolEvent;

/**
 * Rest des Events ist identisch mit AccommodationRequestAccepted, deswegen nur getTitle() abgeleitet und sonst extends.
 */
class AccommodationRequestRejected extends AccommodationRequestAccepted implements ManageableEvent, AccommodationEvent, SchoolEvent
{
	use Dispatchable,
		ManageableEventTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Unterkunftsanbieter hat eine Zuweisungsanfrage abgelehnt');
	}

}