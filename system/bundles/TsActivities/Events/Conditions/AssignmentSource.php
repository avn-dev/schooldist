<?php

namespace TsActivities\Events\Conditions;

use Tc\Interfaces\EventManager\Manageable;
use TsActivities\Enums\AssignmentSource as SourceEnum;
use Tc\Interfaces\Events\Settings;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Traits\Events\ManageableTrait;

class AssignmentSource implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Quelle');
	}

	public static function toReadable(Settings $settings): string
	{
		$label = SourceEnum::from($settings->getSetting('source'))->getLabelText(EventManager::l10n());

		return sprintf(
			EventManager::l10n()->translate('Wenn Quelle "%s"'),
			$label
		);
	}

	public function passes($event): bool
	{
		$source = $event->getSource();
		return $source->value === $this->managedObject->getSetting('source');
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$sources = collect(SourceEnum::cases())
			->mapWithKeys(fn ($enum) => [$enum->value => $enum->getLabelText(EventManager::l10n())]);

		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Quelle'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_source',
			'required' => true,
			'select_options' => $sources->toArray()
		]));
	}
}