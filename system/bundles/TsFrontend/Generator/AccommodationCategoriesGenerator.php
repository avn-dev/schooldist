<?php

namespace TsFrontend\Generator;

class AccommodationCategoriesGenerator extends \Ext_TC_Frontend_Combination_Abstract {

	/**
	 * {@inheritdoc}
	 */
	protected function _default() {

		$language = $this->_oCombination->items_language;
		$schoolId = (int)$this->_oCombination->items_school;
		
		$school = \Ext_Thebing_School::getInstance($schoolId);

		$categories = $school->getAccommodationList(false);
		
		$this->_assign('language', $language);
		$this->_assign('school', $school);
		$this->_assign('categories', $categories);
		
	}
	
}