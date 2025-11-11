<?php

namespace Ts\Events\Inquiry\Conditions;

use Illuminate\Support\Arr;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Interfaces\Events\Inquiry\JourneyCourseEvent;
use Ts\Interfaces\Events\InquiryEvent;

class Course implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Kunde hat bestimmten Kurs gebucht');
	}

	public static function toReadable(Settings $settings): string
	{
		$courseNames = array_map(function ($schoolId) {
			$course = \Ext_Thebing_Tuition_Course::getInstance($schoolId);
			return $course->getName();
		}, Arr::wrap($settings->getSetting('course_ids')));

		$length = count($courseNames);

		if ($length > 5) {
			$courseNames = array_slice($courseNames, 0, 3);
			$courseNames[] = sprintf('(+%d)', $length - 3);
		}

		return sprintf(
			EventManager::l10n()->translate('Wenn Kurs "%s" gebucht wurde'),
			implode(', ', $courseNames)
		);
	}

	public function passes(InquiryEvent|JourneyCourseEvent $event): bool
	{
		$courseIds = $this->managedObject->getSetting('course_ids', []);

		if ($event instanceof JourneyCourseEvent) {
			$journeyCourses = [$event->getJourneyCourse()];
		} else {
			// Komplette Buchung
			$journeyCourses = $event->getInquiry()->getCourses();
		}

		foreach ($journeyCourses as $journeyCourse) {
			/* @var \Ext_TS_Inquiry_Journey_Course $journeyCourse */

			// Kurs passt direkt zu den ausgewählten Kursen
			if (in_array($journeyCourse->course_id, $courseIds)) {
				return true;
			}

			// Unterkurse prüfen
			$courses = $journeyCourse->getProgram()->getCourses();
			foreach ($courses as $course) {
				if (in_array($course->id, $courseIds)) {
					return true;
				}
			}
		}

		return false;
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Kurse'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_course_ids',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => true,
			'select_options' => \Ext_Thebing_Tuition_Course::query()->get()
				->mapWithKeys(fn (\Ext_Thebing_Tuition_Course $course) => [$course->id => $course->getName()])
		]));

	}
}
