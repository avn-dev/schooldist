<?php

namespace Ts\Events\Inquiry\Conditions;

use Illuminate\Support\Arr;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\AccommodationEvent;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;

class AccommodationCategoryProvider implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Unterkunftsanbieter gehört bestimmter Unterkunftskategorie an');
	}

	public static function toReadable(Settings $settings): string
	{
		return AccommodationCategoryCustomer::toReadable($settings, ' zum Unterkunftsanbieter gehört');
	}

	public function passes(AccommodationEvent $event): bool
	{
		$categoryIds = $this->managedObject->getSetting('accommodation_category_ids', []);
		$accommodationCategorys = $event->getAccommodation()->getCategories();

		foreach ($accommodationCategorys as $accommodationCategory) {
			if (in_array($accommodationCategory->getId(), $categoryIds)) {
				return true;
			}
		}

		return false;
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		AccommodationCategoryCustomer::prepareGui2Dialog($dialog, $tab, $dataClass);
	}
}
