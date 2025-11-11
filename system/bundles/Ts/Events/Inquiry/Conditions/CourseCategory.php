<?php

namespace Ts\Events\Inquiry\Conditions;

use Illuminate\Support\Arr;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Ts\Events\Inquiry\Services\CourseBooked;
use Ts\Interfaces\Events\Inquiry\JourneyCourseEvent;
use Ts\Interfaces\Events\InquiryEvent;

class CourseCategory implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Kunde hat bestimmte Kurskategorie gebucht');
	}

	public static function toReadable(Settings $settings): string
	{
		$categoryNames = array_map(function ($categoryId) {
			$category = \Ext_Thebing_Tuition_Course_Category::getInstance($categoryId);
			return $category->getName();
		}, Arr::wrap($settings->getSetting('category_ids')));

		return sprintf(
			EventManager::l10n()->translate('Wenn Kurskategorie "%s" gebucht wurde'),
			implode(', ', $categoryNames)
		);
	}

	public function passes(InquiryEvent|JourneyCourseEvent $event): bool
	{
		$categoryIds = $this->managedObject->getSetting('category_ids', []);

		if ($event instanceof JourneyCourseEvent) {
			$journeyCourses = [$event->getJourneyCourse()];
		} else {
			// Komplette Buchung
			$journeyCourses = $event->getInquiry()->getCourses();
		}

		foreach ($journeyCourses as $journeyCourse) {
			/* @var \Ext_TS_Inquiry_Journey_Course $journeyCourse */

			// Kurs passt direkt zu den ausgewählten Kategorien
			if (in_array($journeyCourse->getCourse()->category_id, $categoryIds)) {
				return true;
			}

			// Unterkurse prüfen
			$courses = $journeyCourse->getProgram()->getCourses();
			foreach ($courses as $course) {
				if (in_array($course->category_id, $categoryIds)) {
					return true;
				}
			}
		}

		return false;
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Kategorie'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_category_ids',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => true,
			'select_options' => \Ext_Thebing_Tuition_Course_Category::query()->get()
				->mapWithKeys(fn ($category) => [$category->id => $category->getName()])
		]));

	}
}
