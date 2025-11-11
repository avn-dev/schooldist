<?php

namespace TsFrontend\Generator;

class CourseCategoryGenerator extends \Ext_TC_Frontend_Combination_Abstract {

	/**
	 * {@inheritdoc}
	 */
	protected function _default() {

		$language = $this->_oCombination->items_language;
		
		$courseCategory = \Ext_Thebing_Tuition_Course_Category::getInstance($this->_oCombination->items_course_category);
		
		$courseCategoryProxy = \Ts\Proxy\Course\Category::getInstance($courseCategory);
		$courseCategoryProxy->setLanguage($language);
		
		$this->_assign('language', $language);
		$this->_assign('courseCategory', $courseCategoryProxy);
		
	}
	
}
