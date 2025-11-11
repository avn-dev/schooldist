<?php

namespace Ts\Events\Inquiry\Conditions;

use Illuminate\Support\Arr;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\InquiryEvent;

class CreatorCondition implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Ersteller');
	}

	public static function toReadable(Settings $settings): string
	{
		$options = \Ext_TS_Inquiry_Index_Gui2_Data::getCreatorOptions();
		$creators = collect($settings->getSetting('creator_ids'))
			->map(fn($value) => $options[$value])
			->join('; ');

		return sprintf(
			EventManager::l10n()->translate('Wenn Ersteller "%s"'),
			$creators
		);
	}

	public function passes(InquiryEvent $event)
	{
		$creatorIds = Arr::wrap($this->managedObject->getSetting('creator_ids'));
		$inquiry = $event->getInquiry();

		return in_array($inquiry->getCreatorIdForIndex(), $creatorIds);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Ersteller'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_creator_ids',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => true,
			'select_options' => \Ext_TS_Inquiry_Index_Gui2_Data::getCreatorOptions()
		]));
	}
}
