<?php

namespace Ts\Events\Inquiry\Conditions;

use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Interfaces\Events\SchoolEvent;

class AgeLimitation implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Bestimmtes Alter');
	}

	public static function toReadable(Settings $settings): string
	{
		$operators = self::getOperatorOptions();

		return sprintf(
			EventManager::l10n()->translate('Wenn das Alter des Kunden %s %s'),
			$operators[$settings->getSetting('operator')] ?? '',
			$settings->getSetting('age'),
		);
	}

	public function passes(InquiryEvent $event): bool
	{
		$customer = $event->getInquiry()->getCustomer();
		[$operator, $age] = $this->getAgeLimitation($event);

		return $this->check($customer->getAge(), $operator, $age);
	}

	protected function getAgeLimitation(SchoolEvent $event): array
	{
		$age = $this->managedObject->getSetting('age', 9999);
		$operator = $this->managedObject->getSetting('operator', '<');
		return [$operator, $age];
	}

	protected function check($value1, $operator, $value2): bool
	{
		return match ($operator) {
			'<' => $value1 < $value2,
			'<=' => $value1 <= $value2,
			'>' => $value1 > $value2,
			'>=' => $value1 >= $value2,
			'=' => $value1 == $value2,
			'!=' => $value1 != $value2,
			default => false
		};
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createMultiRow(EventManager::l10n()->translate('Alter'), [
			'db_alias' => 'tc_emc',
			'items' => [
				[
					'input' => 'select',
					'db_column' => 'meta_operator',
					'style' => 'width: 40px;',
					'select_options' => self::getOperatorOptions(),
					'required' => true,
				],
				[
					'input' => 'input',
					'db_column' => 'meta_age',
					'style' => 'width: 60px;',
					'required' => true,
				]
			]
		]));
	}

	private static function getOperatorOptions(): array
	{
		return [
			'' => '',
			'<' => '<',
			'<=' => '≤',
			'>' => '>',
			'>=' => '≥',
			'=' => '=',
			'!=' => '≠',
		];
	}

}
