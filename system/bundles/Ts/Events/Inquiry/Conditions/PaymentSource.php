<?php

namespace Ts\Events\Inquiry\Conditions;

use Illuminate\Support\Arr;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Events\Inquiry\NewPayment;

class PaymentSource implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Herkunft der Zahlung');
	}

	public static function toReadable(Settings $settings): string
	{
		$sourcesName = array_map(function ($source) {
			return self::getSourcesOptions()[$source] ?? $source;
		}, Arr::wrap($settings->getSetting('sources')));

		return sprintf(
			EventManager::l10n()->translate('Herkunft der Zahlung ist "%s"'),
			implode(', ', $sourcesName)
		);
	}

	public function passes(NewPayment $event): bool
	{
		$sources = $this->managedObject->getSetting('sources', []);
		return in_array($event->getSource(), $sources);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Herkunft'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_sources',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => true,
			'select_options' => self::getSourcesOptions()
		]));
	}

	public static function getSourcesOptions(): array
	{
		$options = [
			'backend' => EventManager::l10n()->translate('Zahlungsdialog'),
			'frontend_payment_form' => EventManager::l10n()->translate('Frontend: Zahlungsformular'),
			'open_banking' => EventManager::l10n()->translate('Open Banking')
		];

		\System::wd()->executeHook('ts_events_new_payment_sources', $options);

		return $options;
	}
}
