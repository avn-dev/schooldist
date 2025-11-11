<?php

class Ext_Thebing_Management_Settings_TuitionTimes_Gui2_Selection_Course extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$entity) {
		/** @var Ext_Thebing_Management_Settings_TuitionTime $entity */

		if (empty($entity->tuition_time_id)) {
			return [];
		}

		$schoolId = $entity->getTuitionTime()->getSchool();
		$options = \Ext_Thebing_Management_Settings_TuitionTimes_Gui2_Data::getCourseOptions();

		// Kurse abhängig von Schule der Standardzeit
		$options = array_filter($options, function ($id) use ($schoolId) {
			$course = Ext_Thebing_Tuition_Course::getInstance($id);
			return $course->school_id == $schoolId->id;
		}, ARRAY_FILTER_USE_KEY);

		return $options;

	}

}