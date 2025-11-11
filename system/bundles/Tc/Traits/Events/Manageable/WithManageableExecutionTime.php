<?php

namespace Tc\Traits\Events\Manageable;

use Tc\Gui2\Data\EventManagementData;
use Tc\Facades\EventManager;
use Tc\Interfaces\Events\Settings;

trait WithManageableExecutionTime
{
	public static function toReadable(Settings $settings): string
	{
		$executionDay = $settings->getSetting('execution_day');
		$weekday = self::getWeekdaySelectOptions()[$executionDay];
		if ($executionDay !== null) {
			$weekday = sprintf(EventManager::l10n()->translate('Jeden %s'), $weekday);
		}

		return sprintf(
			EventManager::l10n()->translate('%s um %s'),
			$weekday,
			$settings->getSetting('execution_time').':00'
		);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $eventTab, EventManagementData $data): void
	{
		self::addExecutionTimeRow($dialog, $eventTab, $data);
		self::addExecutionWeekdayRow($dialog, $eventTab, $data);
	}

	public static function addExecutionTimeRow(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $eventTab, EventManagementData $data): void
	{
		$eventTab->setElement($dialog->createRow(EventManager::l10n()->translate('Ausführungszeit'), 'select', [
			'db_alias' => 'tc_em',
			'db_column' => 'execution_time',
			'select_options' => \Ext_TC_Util::getHours()
		]));
	}

	public static function addExecutionWeekdayRow(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $eventTab, EventManagementData $data): void
	{
		$eventTab->setElement($dialog->createRow(EventManager::l10n()->translate('Wochentag'), 'select', [
			'db_alias' => 'tc_em',
			'db_column' => 'execution_day',
			'format' => new \Ext_Gui2_View_Format_Null(),
			'select_options' => self::getWeekdaySelectOptions()
		]));

		self::addExecutionWeekendRow($dialog, $eventTab, $data);
	}

	public static function addExecutionWeekendRow(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $eventTab, EventManagementData $data): void
	{
		$eventTab->setElement($dialog->createRow(EventManager::l10n()->translate('Wochenende einbeziehen'), 'checkbox', [
			'db_alias' => 'tc_em',
			'db_column' => 'execution_weekend',
			'format' => new \Ext_Gui2_View_Format_Null(),
			'dependency_visibility' => [
				'db_column' => 'execution_day',
				'db_alias' => 'tc_em',
				'on_values' => ['']
			]
		]));
	}

	protected static function getWeekdaySelectOptions(): array
	{
		$l10n = EventManager::l10n();
		$options = \Ext_TC_Util::getWeekdaySelectOptions($l10n->getLanguage());
		return \Ext_TC_Util::addEmptyItem($options, $l10n->translate('Täglich'), '');
	}

}
