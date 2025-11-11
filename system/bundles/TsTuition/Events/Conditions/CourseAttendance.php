<?php

namespace TsTuition\Events\Conditions;

use Carbon\Carbon;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use TsTuition\Events\CurrentAllocatedStudents;

class CourseAttendance implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Kursbezogene Anwesenheit (Gesamter Zeitraum)');
	}

	public static function toReadable(Settings $settings): string
	{
		$modes = self::getModes();

		return sprintf(
			EventManager::l10n()->translate('Wenn kursbezogene Anwesenheit (%s) zwischen %s%% und %s%%'),
			$modes[$settings->getSetting('mode')] ?? '',
			\Ext_Thebing_Format::Number((float)$settings->getSetting('attendance_from')),
			\Ext_Thebing_Format::Number((float)$settings->getSetting('attendance_until'))
		);
	}

	public function passes(CurrentAllocatedStudents $event): bool
	{
		$mode = $this->managedObject->getSetting('mode');
		$attendanceFrom = (float)$this->managedObject->getSetting('attendance_from');
		$attendanceUntil = (float)$this->managedObject->getSetting('attendance_until');

		$journeyCourse = $event->getJourneyCourse();

		// Falls der Kurs durch Ferien gesplittet wurde muss hier der früheste Start genommen werden
		$earliestFrom = collect($journeyCourse->getRelatedServices())
			->map(fn (\Ext_TS_Inquiry_Journey_Course $service) => $service->from)
			->filter(fn ($from) => !empty($from) && $from !== '0000-00-00')
			->map(fn ($from) => Carbon::parse($from))
			->sort()
			->first();

		$filters = [
			'holiday_splittings' => true,
			'week_from' => $earliestFrom->format('Y-m-d'),
			'week_until' => $event->getWeek()->toDateString()
		];

		if ($mode === 'expected') {
			$filters['expected'] = true;
		}

		$attendance = (float)(new \Ext_Thebing_Tuition_Attendance_Index())
			->getAttendanceForJourneyCourseProgramService($journeyCourse, $event->getProgramService(), true, $filters);

		return $this->check($attendance, $attendanceFrom, $attendanceUntil);
	}

	public function check(float $attendance, float $value1, float $value2): bool
	{
		$comparison1 = bccomp($attendance, $value1, 2);
		$comparison2 = bccomp($attendance, $value2, 2);

		return ($comparison1 === 1 || $comparison1 === 0) && ($comparison2 === -1 || $comparison2 === 0);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createMultiRow(EventManager::l10n()->translate('Anwesenheit'), [
			'db_alias' => 'tc_emc',
			'items' => [
				[
					'input' => 'select',
					'db_column' => 'meta_mode',
					'select_options' => self::getModes(),
					'required' => true,
					'text_after' => '&nbsp;',
				],
				[
					'input' => 'input',
					'db_column' => 'meta_attendance_from',
					'style' => 'width: 60px;',
					'required' => true,
					'text_after' => '&nbsp;-',
					'format' => new \Ext_Thebing_Gui2_Format_Float(),
				],
				[
					'input' => 'input',
					'db_column' => 'meta_attendance_until',
					'style' => 'width: 60px;',
					'required' => true,
					'text_after' => '%',
					'format' => new \Ext_Thebing_Gui2_Format_Float(),
				]
			]
		]));
	}

	private static function getModes(): array
	{
		return [
			'total' => EventManager::l10n()->translate('Total'),
			'expected' => EventManager::l10n()->translate('Erwartet')
		];
	}

}
