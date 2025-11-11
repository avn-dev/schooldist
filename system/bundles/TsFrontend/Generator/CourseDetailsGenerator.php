<?php

namespace TsFrontend\Generator;

class CourseDetailsGenerator extends \Ext_TC_Frontend_Combination_Abstract {

	public $overwritable = true;

	private $superordinateCourse;
	
	/**
	 * {@inheritdoc}
	 */
	protected function _default() {

		$language = $this->_oCombination->items_language;
		
		$metaCourseService = new \TsFrontend\Service\SuperordinateCourses($language);
		$courses = $metaCourseService->getCourses();

		if(isset($courses[$this->_oCombination->items_course_slug])) {

			$this->superordinateCourse = $courses[$this->_oCombination->items_course_slug];
			
			$this->_assign('course', $this->superordinateCourse);

		}

	}
	
	public function getContent() {
		
		$content = parent::getContent();

		$content['title'] = $this->superordinateCourse->getName();
		
		return $content;
	}
	
}