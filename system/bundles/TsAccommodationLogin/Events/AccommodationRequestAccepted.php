<?php

namespace TsAccommodationLogin\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\Events\AccommodationEvent;
use Tc\Listeners\SendAccommodationProviderNotification;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Interfaces\Events\SchoolEvent;
use Ts\Listeners\SendSchoolNotification;

class AccommodationRequestAccepted implements ManageableEvent, AccommodationEvent, SchoolEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication;

	public function __construct(
		private readonly \Ext_TS_Inquiry_Journey_Accommodation $journeyAccommodation
	) {}

	public function getAccommodation(): \Ext_Thebing_Accommodation
	{
		return \Ext_Thebing_Accommodation::getInstance($this->journeyAccommodation->accommodation_id);
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->journeyAccommodation->getSchool();
	}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Unterkunftsanbieter hat eine Zuweisungsanfrage angenommen');
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$journeyAccommodation = ($event) ? $event->journeyAccommodation : new \Ext_TS_Inquiry_Journey_Accommodation();
		return $journeyAccommodation->getPlaceholderObject();
	}

	public static function manageListenersAndConditions(): void
	{
		self::addManageableListener(SendSchoolNotification::class);
		self::addManageableListener(SendAccommodationProviderNotification::class);
	}

}