<?php

namespace TsFrontend\Generator;

class CourseListGenerator extends \Ext_TC_Frontend_Combination_Abstract {

	/**
	 * {@inheritdoc}
	 */
	protected function _default() {

		$language = $this->_oCombination->items_language;
		
		$flexFilter = [];
		foreach($this->_oCombination->items as $flexItem) {
			if(
				!empty($flexItem['item_value']) &&
				strpos($flexItem['item'], 'flex_') === 0
			) {
				$flexFilter[str_replace('flex_', '', $flexItem['item'])][] = $flexItem['item_value'];
			}
		}
		$courseCategoryId = (int)$this->_oCombination->items_course_category;
		$schoolIds = (array)$this->_oCombination->items_schools;		

		$superordinateCourseService = new \TsFrontend\Service\SuperordinateCourses($language);
		$courses = $superordinateCourseService->getCourses($courseCategoryId, $schoolIds, $flexFilter);

		if(!empty($schoolIds)) {
			$this->_assign('schoolIds', $schoolIds);
		}

		$this->_assign('courses', $courses);
		
	}
	
}