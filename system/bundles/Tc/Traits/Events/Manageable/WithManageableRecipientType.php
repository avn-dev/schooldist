<?php

namespace Tc\Traits\Events\Manageable;

use Tc\Facades\EventManager;

trait WithManageableRecipientType
{
	public static function addRecipientTypeRow(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $eventTab): void
	{
		$eventTab->setElement($dialog->createRow(EventManager::l10n()->translate('Empfänger-Typ'), 'select', array(
			'db_column' => 'meta_recipient_type',
			'required' => true,
			'select_options' => self::getRecipientTypeSelectOptions()
		)));
	}

	protected static function getRecipientTypeSelectOptions(): array
	{
		return [
			'all_customers' => EventManager::l10n()->translate('Alle Schüler'),
			'current_customers' => EventManager::l10n()->translate('Aktuelle Schüler'),
			'current_and_future_customers' => EventManager::l10n()->translate('Aktuelle und zukünftige Schüler'),
			'current_and_old_customers' => EventManager::l10n()->translate('Aktuelle und alte Schüler')
		];
	}

}
