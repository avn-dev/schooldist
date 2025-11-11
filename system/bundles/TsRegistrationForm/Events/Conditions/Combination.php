<?php

namespace TsRegistrationForm\Events\Conditions;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use TsFrontend\Interfaces\Events\CombinationEvent;

class Combination implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Auf Frontend-Kombination begrenzen');
	}

	public static function toReadable(Settings $settings): string
	{
		$combinationNames = \Ext_TS_Frontend_Combination::query()
			->whereIn('id', Arr::wrap($settings->getSetting('combination_ids')))
			->pluck('name');

		return sprintf(
			EventManager::l10n()->translate('Wenn Kombination "%s"'),
			$combinationNames->implode(', ')
		);
	}

	public function passes(CombinationEvent $event): bool
	{
		$combination = $event->getCombination();
		return in_array($combination->id, $this->managedObject->getSetting('combination_ids', []));
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Kombinationen'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_combination_ids',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => true,
			'select_options' => self::getCombinations()
		]));
	}

	private static function getCombinations(): Collection
	{
		return \Ext_TS_Frontend_Combination::query()
			->where('usage', 'registration_v3')
			->get()
			->mapWithKeys(fn ($combination) => [$combination->id => $combination->name]);
	}
}