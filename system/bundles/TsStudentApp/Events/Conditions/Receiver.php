<?php

namespace TsStudentApp\Events\Conditions;

use Core\Helper\BundleConfig;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use TsStudentApp\Events\AppMessageReceived;

class Receiver implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Empfänger');
	}

	public static function toReadable(Settings $settings): string
	{
		$receiver = self::getReceiverSelectOptions()[$settings->getSetting('receiver')];

		return sprintf(
			EventManager::l10n()->translate('Wenn Empfänger "%s"'),
			$receiver
		);
	}

	public function passes(AppMessageReceived $event): bool
	{
		$receiver = $event->getReceiver();
		return ($receiver::class === $this->managedObject->getSetting('receiver'));
	}

	public static function getReceiverSelectOptions(): array
	{
		$threads = BundleConfig::of('TsStudentApp')->get('messenger.threads');

		return collect($threads)
			->mapWithKeys(fn ($thread) => [$thread['entity'] => EventManager::l10n()->translate($thread['label'])])
			->toArray();
	}
	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Emfänger'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_receiver',
			'select_options' => self::getReceiverSelectOptions()
		]));

	}
}