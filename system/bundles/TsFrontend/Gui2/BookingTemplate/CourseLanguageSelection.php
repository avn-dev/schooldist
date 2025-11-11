<?php

namespace TsFrontend\Gui2\BookingTemplate;

use \TsFrontend\Entity\BookingTemplate;

class CourseLanguageSelection extends \Ext_Gui2_View_Selection_Abstract
{
	public function getOptions($selectedIds, $saveField, &$entity)
	{
		/** @var BookingTemplate $entity */
		$language = \System::getInterfaceLanguage();
		$options = ['' => ''];

		if ($entity->course_id) {
			$course = \Ext_Thebing_Tuition_Course::getInstance($entity->course_id);
			$courseLanguages = $course->getCourseLanguages();
			foreach ($courseLanguages as $courseLanguage) {
				$options[$courseLanguage->id] = $courseLanguage->getName($language);
			}
		}

		return $options;
	}
}