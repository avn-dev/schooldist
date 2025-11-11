<?php

namespace Ts\Events\Inquiry\Conditions;

use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\InquiryEvent;

class InquiryType implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Buchungstyp');
	}

	public static function toReadable(Settings $settings): string
	{
		return sprintf(
			EventManager::l10n()->translate('Wenn Buchungstyp "%s"'),
			self::getBookingTypeSelectOptions()[$settings->getSetting('booking_type')]
		);
	}

	public function passes(InquiryEvent $event) {

		$type = $this->managedObject->getSetting('booking_type');
		$inquiry = $event->getInquiry();

		if ($type === 'agency') {
			return $inquiry->hasAgency();
		} else if ($type === 'group') {
			return $inquiry->hasGroup();
		}

		return (!$inquiry->hasAgency() && !$inquiry->hasGroup());
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Buchung'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_booking_type',
			'select_options' => self::getBookingTypeSelectOptions(),
			'required' => true,
		]));
	}

	protected static function getBookingTypeSelectOptions(): array
	{
		$l10n = EventManager::l10n();

		return [
			'normal' => $l10n->translate('Direktbuchungen'),
			'agency' => $l10n->translate('Agenturbuchung'),
			'group' => $l10n->translate('Gruppenbuchung'),
		];
	}
}
