<?php

namespace Ts\Events\Inquiry\Services\Conditions;

use Tc\Interfaces\EventManager\Manageable;
use Tc\Traits\Events\ManageableTrait;
use Ts\Events\Inquiry\Services\NewJourneyTransfer;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Ts\Interfaces\Events\InquiryEvent;

class TransferDataMissing implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Transferdaten fehlen');
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
			if ($this->checkJourneyTransferOnMissingData($journeyTransfer)) {
				// Nicht alle Daten ausgefüllt
				return true;
			}
		}

		return false;
	}

	private function checkJourneyTransferOnMissingData(\Ext_TS_Inquiry_Journey_Transfer $journeyTransfer): bool
	{
		if (
			(bool)$this->managedObject->getSetting('missing_start', false) &&
			(int)$journeyTransfer->start === 0
		) {
			return true;
		}

		if (
			(bool)$this->managedObject->getSetting('missing_end', false) &&
			(int)$journeyTransfer->end === 0
		) {
			return true;
		}

		if (
			(bool)$this->managedObject->getSetting('missing_airline', false) &&
			empty($journeyTransfer->airline) &&
			$journeyTransfer->transfer_type != \Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL
		) {
			return true;
		}

		if (
			(bool)$this->managedObject->getSetting('missing_flightnumber', false) &&
			empty($journeyTransfer->flightnumber) &&
			$journeyTransfer->transfer_type != \Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL
		) {
			return true;
		}

		if (
			(bool)$this->managedObject->getSetting('missing_transferdate', false) &&
			empty($journeyTransfer->transfer_date)
		) {
			return true;
		}

		if (
			(bool)$this->managedObject->getSetting('missing_transfer_time', false) &&
			empty($journeyTransfer->transfer_time) &&
			$journeyTransfer->transfer_type != \Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL
		) {
			return true;
		}

		if (
			(bool)$this->managedObject->getSetting('missing_pickup', false) &&
			empty($journeyTransfer->pickup)
		) {
			return true;
		}

		return false;
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$l10n = EventManager::l10n();

		$tab->setElement($dialog->createRow($l10n->translate('Anreiseort'), 'checkbox', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_missing_start'
		]));

		$tab->setElement($dialog->createRow($l10n->translate('Zielort'), 'checkbox', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_missing_end'
		]));

		$tab->setElement($dialog->createRow($l10n->translate('Fluggesellschaft'), 'checkbox', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_missing_airline'
		]));

		$tab->setElement($dialog->createRow($l10n->translate('Flugnummer'), 'checkbox', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_missing_flightnumber'
		]));

		$tab->setElement($dialog->createRow($l10n->translate('Datum'), 'checkbox', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_missing_transferdate'
		]));

		$tab->setElement($dialog->createRow($l10n->translate('Abreiseuhrzeit'), 'checkbox', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_missing_transfer_time'
		]));

		$tab->setElement($dialog->createRow($l10n->translate('Abholzeit (Anbieter)'), 'checkbox', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_missing_pickup'
		]));

	}
}
