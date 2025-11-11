<?php

namespace TsAccommodation\Events\Conditions;

use Tc\Facades\EventManager;
use Illuminate\Support\Arr;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\AccommodationEvent;
use Tc\Traits\Events\ManageableTrait;

class ActiveCondition implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Unterkunft ist aktiv');
	}

	public function passes(AccommodationEvent $event): bool
	{
		return $event->getAccommodation()->isValid();
	}

}
