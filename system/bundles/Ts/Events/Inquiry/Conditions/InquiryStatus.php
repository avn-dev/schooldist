<?php

namespace Ts\Events\Inquiry\Conditions;

use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\InquiryEvent;

class InquiryStatus implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Buchungsstatus');
	}

	public static function toReadable(Settings $settings): string
	{
		return sprintf(
			EventManager::l10n()->translate('Wenn Buchungsstatus "%s %s"'),
			self::getOperatorOptions()[$settings->getSetting('operator')],
			self::getStatusOptions()[$settings->getSetting('status')]
		);
	}

	public function passes(InquiryEvent $event): bool
	{
		$status = $this->managedObject->getSetting('status');
		$operator = $this->managedObject->getSetting('operator');

		$inquiry = $event->getInquiry();

		if ($status === 'confirmed') {
			if ($operator === 'is_not') {
				return !$inquiry->isConfirmed();
			} else {
				return $inquiry->isConfirmed();
			}
		} else if ($status === 'cancelled') {
			if ($operator === 'is_not') {
				return !$inquiry->isCancelled();
			} else {
				return $inquiry->isCancelled();
			}
		} else if ($status === 'checked_in') {
			if ($operator === 'is_not') {
				return !$inquiry->isCheckedIn();
			} else {
				return $inquiry->isCheckedIn();
			}
		} else if ($status === 'sponsored') {
			if ($operator === 'is_not') {
				return !$inquiry->isSponsored();
			} else {
				return $inquiry->isSponsored();
			}
		}

		return false;
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createMultiRow(EventManager::l10n()->translate('Buchung'), [
			'db_alias' => 'tc_emc',
			'items' => [
				[
					'input' => 'select',
					'db_column' => 'meta_operator',
					'select_options' => self::getOperatorOptions(),
					'required' => true,
				],
				[
					'input' => 'select',
					'db_column' => 'meta_status',
					'select_options' => self::getStatusOptions(),
					'required' => true,
				]
			]
		]));
	}

	public static function getOperatorOptions(): array
	{
		return [
			'is' => EventManager::l10n()->translate('Ist'),
			'is_not' => EventManager::l10n()->translate('Ist nicht'),
		];
	}

	public static function getStatusOptions(): array
	{
		return [
			'confirmed' => EventManager::l10n()->translate('Bestätigt'),
			'cancelled' => EventManager::l10n()->translate('Storniert'),
			'checked_in' => EventManager::l10n()->translate('Eingecheckt'),
			'sponsored' => EventManager::l10n()->translate('Gesponsort'),
		];
	}
}
