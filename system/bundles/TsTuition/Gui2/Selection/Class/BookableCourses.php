<?php

namespace TsTuition\Gui2\Selection\Class;

class BookableCourses extends \Ext_Gui2_View_Selection_Abstract
{
	/**
	 * @param array $ids
	 * @param array $field
	 * @param \Ext_Thebing_Tuition_Class $object
	 * @return string[]
	 */
	public function getOptions($ids, $field, &$object)
	{
		$courses = array_map(fn(\Ext_Thebing_Tuition_Course $course) => $course->getName(), $object->getJoinTableObjects('courses'));

		return ['' => \L10N::t('nicht online verfügbar', \Ext_Thebing_Tuition_Class_Gui2::TRANSLATION_PATH)] + $courses;
	}
}
