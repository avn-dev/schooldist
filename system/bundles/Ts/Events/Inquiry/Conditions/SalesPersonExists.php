<?php

namespace Ts\Events\Inquiry\Conditions;

use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\InquiryEvent;

class SalesPersonExists implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Vertriebsmitarbeiter vorhanden');
	}

	public static function toReadable(Settings $settings): string
	{
		if ((int)$settings->getSetting('sales_person_exists_operator') === 0) {
			$operator = 'nicht ';
		} else {
			$operator = '';
		}

		return EventManager::l10n()->translate('Wenn der Vertriebsmitarbeiter '.$operator.'vorhanden ist');

	}

	public function passes(InquiryEvent $event): bool
	{
		$operator = (int)$this->managedObject->getSetting('sales_person_exists_operator');
		$salesPerson = $event->getInquiry()->getSalesPerson();

		return ($operator === 0) ? $salesPerson == null : $salesPerson !== null;
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Vertriebsmitarbeiter vorhanden'),
			'select', [
				'db_alias' => 'tc_emc',
				'db_column' => 'meta_sales_person_exists_operator',
				'select_options' => \Ext_TC_Util::getYesNoArray()
			]
		));
	}

}
