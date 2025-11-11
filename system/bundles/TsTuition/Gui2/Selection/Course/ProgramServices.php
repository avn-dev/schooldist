<?php

namespace TsTuition\Gui2\Selection\Course;

use TsTuition\Entity\Course\Program\Service;

class ProgramServices extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$services = [];

		// TODO - umstellen sobald mehrere Leistungstypen zur Verfügung stehen
		$type = Service::TYPE_COURSE;
		if($this->oJoinedObject) {
			$type = $this->oJoinedObject->type;
		}

		switch ($type) {
			case Service::TYPE_COURSE:
				$school = \Ext_Thebing_School::getSchoolFromSession();

				$courses = \Ext_Thebing_Tuition_Course::query()
					->onlyValid()
					->where('ktc.school_id', $school->getId())
					->get();

				$services = $courses
					->filter(function(\Ext_Thebing_Tuition_Course $course) {
						return !$course->canHaveChildCourses();
					})
					->mapWithKeys(function (\Ext_Thebing_Tuition_Course $course) {
						return [$course->id => $course->getName()];
					})
					->toArray();

				break;
		}

		if(!empty($services)) {
			$services = \Ext_Thebing_Util::addEmptyItem($services);
		}

		return $services;
	}

}
