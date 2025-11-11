<?php

namespace Ts\Events\Inquiry\Conditions;

use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\InquiryEvent;

class TypeCondition implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Typ');
	}

	public static function toReadable(Settings $settings): string
	{
		return sprintf(
			EventManager::l10n()->translate('Wenn Typ "%s"'),
			self::getTypeOptions()[$settings->getSetting('type')]
		);
	}

	public function passes(InquiryEvent $event)
	{
		$type = $this->managedObject->getSetting('type');
		$inquiry = $event->getInquiry();

		return !!match ($type) {
			'booking' => $inquiry->type & \Ext_TS_Inquiry::TYPE_BOOKING,
			'enquiry' => $inquiry->type & \Ext_TS_Inquiry::TYPE_ENQUIRY
		};
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Typ'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_type',
			'select_options' => self::getTypeOptions(),
			'required' => true,
		]));
	}

	private static function getTypeOptions()
	{
		$l10n = EventManager::l10n();

		return [
			'booking' => $l10n->translate('Buchung'),
			'enquiry' => $l10n->translate('Anfrage')
		];
	}
}
