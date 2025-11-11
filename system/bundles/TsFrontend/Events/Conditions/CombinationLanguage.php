<?php

namespace TsFrontend\Events\Conditions;

use Illuminate\Support\Arr;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use TsFrontend\Interfaces\Events\CombinationEvent;

class CombinationLanguage implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Auf Sprache begrenzen');
	}

	public static function toReadable(Settings $settings): string
	{
		$combinationLanguages = collect(\Ext_Thebing_Data::getSystemLanguages())
			->intersectByKeys(array_flip(Arr::wrap($settings->getSetting('languages'))));

		return sprintf(
			EventManager::l10n()->translate('Wenn Sprache "%s"'),
			$combinationLanguages->implode(', ')
		);
	}

	public function passes(CombinationEvent $event): bool
	{
		$language = $event->getLanguage();
		return in_array($language, $this->managedObject->getSetting('languages', []));
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Sprachen'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_languages',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => true,
			'select_options' => \Ext_Thebing_Data::getSystemLanguages()
		]));
	}

}