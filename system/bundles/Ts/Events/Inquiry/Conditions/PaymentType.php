<?php

namespace Ts\Events\Inquiry\Conditions;

use Illuminate\Support\Arr;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Events\Inquiry\NewPayment;

class PaymentType implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Art der Zahlung');
	}

	public static function toReadable(Settings $settings): string
	{
		$typeNames = array_map(function ($type) {
			return \Ext_Thebing_Inquiry_Payment::getTypeOptions()[$type] ?? $type;
		}, Arr::wrap($settings->getSetting('types')));

		return sprintf(
			EventManager::l10n()->translate('Art der Zahlung ist "%s"'),
			implode(', ', $typeNames)
		);
	}

	public function passes(NewPayment $event): bool
	{
		$types = $this->managedObject->getSetting('types', []);
		return in_array($event->getType(), $types);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Art'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_types',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => true,
			'select_options' => \Ext_Thebing_Inquiry_Payment::getTypeOptions()
		]));
	}
}
