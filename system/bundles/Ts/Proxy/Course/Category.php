<?php

namespace Ts\Proxy\Course;

class Category extends \Ts\Proxy\AbstractProxy {
	
	/**
	 * Primäre Entität
	 * @var string
	 */
	protected $sEntityClass = 'Ext_Thebing_Tuition_Course_Category';

	public function getCourses() {
		
		$courses = $this->oEntity->getJoinedObjectChilds('courses');
		
		$courseProxies = [];
		foreach($courses as $course) {
			$courseProxy = \Ts\Proxy\Course::getInstance($course);
			$courseProxy->setLanguage($this->sLanguage);
			$courseProxies[] = $courseProxy;
		}
		
		return $courseProxies;
	}
	
	public function getCoursesBySchool(\Ext_Thebing_School $school) {
		
		$courses = $this->oEntity->getJoinedObjectChilds('courses');
		
		$courseProxies = [];
		foreach($courses as $course) {
			if($school->id == $course->school_id) {
				$courseProxy = \Ts\Proxy\Course::getInstance($course);
				$courseProxy->setLanguage($this->sLanguage);
				$courseProxies[] = $courseProxy;
			}
		}
		
		return $courseProxies;
	}
	
}