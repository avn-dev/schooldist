<?php

namespace Ts\Events\Inquiry\Conditions;

use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\InquiryEvent;

class DaysSinceLastMessage implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Minimale Anzahl an vergangenen Tagen seit letzter Korrespondenz');
	}

	public function passes(InquiryEvent $event): bool
	{
		/* @var \Ext_TS_Inquiry $inquiry*/
		$inquiry = $event->getInquiry();

		if (null === $lastLog = $inquiry->getLastMailMessageLog()) {
			return true;
		}

		$lastMessageDate = $lastLog->created_index;
		$today = new \DateTime();

		$interval = new \DateInterval('P'.$this->managedObject->getSetting('days', 0).'D');
		$today->sub($interval);

		if ($today > $lastMessageDate) {
			return true;
		}

		return false;
	}

	public static function toReadable(Settings $settings): string
	{
		return sprintf(
			EventManager::l10n()->translate('Wenn mindestens %s Tage seit letzter Korrespondenz vergangen sind'),
			$settings->getSetting('days')
		);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Anzahl an vergangenen Tagen'), 'input', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_days',
			'required' => true,
		]));
	}

}
