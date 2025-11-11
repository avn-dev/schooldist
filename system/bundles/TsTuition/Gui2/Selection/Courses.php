<?php

namespace TsTuition\Gui2\Selection;

class Courses extends \Ext_Gui2_View_Selection_Abstract {


	public function __construct(
		private bool $forCombinedCourses = false
	) { }

	public function getOptions($selectedIds, $saveField, &$wdBasic) {

		$filter = ['type' => [\Ext_Thebing_Tuition_Course::TYPE_PER_WEEK, \Ext_Thebing_Tuition_Course::TYPE_PER_UNIT]];

		if ($this->forCombinedCourses) {
			$filter['combination_selection'] = true;
		}

		/** @var \Ext_Thebing_Tuition_Course $wdBasic */
		$courses = \Ext_Thebing_Tuition_Course::getRepository()->getBySchool($wdBasic->getSchool(), $filter);

		$courses = collect($courses)->mapWithKeys(function (\Ext_Thebing_Tuition_Course $course) {
			return [$course->id => $course->getName()];
		});

		return $courses->toArray();

	}

}