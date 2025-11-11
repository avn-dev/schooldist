<?php

namespace Ts\Events\Inquiry\Conditions;

use Illuminate\Support\Arr;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\InquiryEvent;

class AccommodationCategoryCustomer implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Kunde hat bestimmte Unterkunftskategorie gebucht');
	}

	// $messageAddOn für AccommodationCategoryProvider
	public static function toReadable(Settings $settings, $messageAddOn = null): string
	{
		if ($messageAddOn == null) {
			$messageAddOn = ' gebucht wurde';
		}

		$categories = self::getCategories();

		$categoryIds = $settings->getSetting('accommodation_category_ids');

		$selectedCategories = array_intersect_key($categories, array_flip($categoryIds));

		return sprintf(
			EventManager::l10n()->translate('Wenn Unterkunftskategorie "%s"'.$messageAddOn),
			implode(', ', $selectedCategories)
		);
	}

	public function passes(InquiryEvent $event): bool
	{
		$categoryIds = $this->managedObject->getSetting('accommodation_category_ids', []);
		$accommodations = $event->getInquiry()->getAccommodations();

		foreach ($accommodations as $accommodation) {
			if (in_array($accommodation->getCategory()->getId(), $categoryIds)) {
				return true;
			}
		}

		return false;
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$categories = self::getCategories();

		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Kategorie'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_accommodation_category_ids',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => true,
			'select_options' => $categories
		]));

	}

	public static function getCategories() {
		return \Ext_Thebing_Accommodation_Category::query()->get()
			->mapWithKeys(fn($category) => [$category->id => $category->getName()])->toArray();
	}
}
