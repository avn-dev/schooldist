<?php

namespace TsTuition\Events\Conditions;

use Carbon\Carbon;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use TsTuition\Events\CurrentAllocatedStudents;

class LessonContingent implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Verbleibende Lektionen');
	}

	public static function toReadable(Settings $settings): string
	{
		$operators = self::getOperatorOptions();

		return sprintf(
			EventManager::l10n()->translate('Wenn Anzahl verbleibender Lektionen %s %d'),
			$operators[$settings->getSetting('operator')] ?? '',
			$settings->getSetting('limit')
		);
	}

	public function passes(CurrentAllocatedStudents $event): bool
	{
		$journeyCourse = $event->getJourneyCourse();
		$lessonContingent = $journeyCourse->getLessonsContingent($event->getProgramService());
		$operator = $this->managedObject->getSetting('operator');

		return $this->check($lessonContingent->getRemaining(), $operator, $this->managedObject->getSetting('limit'));
	}

	protected function check(int $value1, string $operator, int $value2): bool
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
		$tab->setElement($dialog->createMultiRow(EventManager::l10n()->translate('Verbleibende Lektionen'), [
			'db_alias' => 'tc_emc',
			'items' => [
				[
					'input' => 'select',
					'db_column' => 'meta_operator',
					'style' => 'width: 50px;',
					'select_options' => self::getOperatorOptions(),
					'required' => true,
					'text_after' => '&nbsp;'
				],
				[
					'input' => 'input',
					'db_column' => 'meta_limit',
					'required' => true,
					'text_after' => '&nbsp;'
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
			'!=' => '≠'
		];
	}

}
