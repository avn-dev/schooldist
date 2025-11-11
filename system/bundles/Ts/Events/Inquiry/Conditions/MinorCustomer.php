<?php

namespace Ts\Events\Inquiry\Conditions;

use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Facades\EventManager;
use Tc\Interfaces\Events\Settings;
use Ts\Interfaces\Events\SchoolEvent;

class MinorCustomer extends AgeLimitation
{
	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Minderjährigkeit');
	}

	public static function toReadable(Settings $settings): string
	{
		if ((int)$settings->getSetting('is_minor') === 1) {
			return EventManager::l10n()->translate('Wenn der Kunde minderjährig ist');
		}

		return EventManager::l10n()->translate('Wenn der Kunde nicht minderjährig ist');
	}

	protected function getAgeLimitation(SchoolEvent $event): array
	{
		if (null !== $school = $event->getSchool()) {
			return ['<', $school->getGrownAge()];
		}

		return parent::getAgeLimitation($event);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Kunde ist minderjährig'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_is_minor',
			'select_options' => \Ext_TC_Util::getYesNoArray()
		]));
	}

}
