<?php

namespace Ts\Events\Conditions;

use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Facades\EventManager;
use Illuminate\Support\Arr;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\SchoolEvent;

class SchoolCondition implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Auf Schule begrenzen');
	}

	public static function toReadable(Settings $settings): string
	{
		$schoolNames = \Ext_Thebing_School::query()
			->whereIn('id', Arr::wrap($settings->getSetting('school_ids')))
			->pluck('ext_1');

		return sprintf(
			EventManager::l10n()->translate('Wenn Schule "%s"'),
			$schoolNames->implode(', ')
		);
	}

	public function passes(SchoolEvent $event): bool
	{
		$school = $event->getSchool();
		return in_array($school->getId(), $this->managedObject->getSetting('school_ids', []));
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Schule'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_school_ids',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => true,
			'select_options' => \Ext_Thebing_School::query()->pluck('ext_1', 'id')
		]));
	}

}
