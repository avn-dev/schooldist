<?php

namespace Ts\Events\Inquiry\Services\Conditions;

use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Events\Inquiry\Services\NewJourneyTransfer;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Ts\Interfaces\Events\InquiryEvent;

class TransferType implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Transfertyp');
	}

	public static function toReadable(Settings $settings): string
	{
		$types = array_intersect_key(
			\Ext_TS_Inquiry_Journey_Transfer::getTransferTypes(EventManager::l10n()),
			array_flip($settings->getSetting('transfer_types', []))
		);

		return sprintf(
			EventManager::l10n()->translate('Wenn Transfertyp ist "%s"'),
			implode(', ', $types)
		);
	}

	public function passes(InquiryEvent $event): bool
	{
		if ($event instanceof NewJourneyTransfer) {
			$journeyTransfers = [$event->getJourneyTransfer()];
		} else {
			// Komplette Buchung
			$journeyTransfers = $event->getInquiry()->getTransfers();
		}

		foreach ($journeyTransfers as $journeyTransfer) {
			if ($this->checkJourneyTransferOnType($journeyTransfer)) {
				// Transfertyp passt
				return true;
			}
		}

		return false;
	}

	private function checkJourneyTransferOnType(\Ext_TS_Inquiry_Journey_Transfer $journeyTransfer): bool
	{
		$types = $this->managedObject->getSetting('transfer_types', []);

		if (in_array($journeyTransfer->transfer_type, $types)) {
			return true;
		}

		return false;
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow($dataClass->t('Transfer'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_transfer_types',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'select_options' => \Ext_TS_Inquiry_Journey_Transfer::getTransferTypes(EventManager::l10n()),
			'style' => 'height: 105px;'
		]));

	}
}
