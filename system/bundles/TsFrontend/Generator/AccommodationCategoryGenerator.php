<?php

namespace TsFrontend\Generator;

class AccommodationCategoryGenerator extends \Ext_TC_Frontend_Combination_Abstract {

	/**
	 * {@inheritdoc}
	 */
	protected function _default() {

		$language = $this->_oCombination->items_language;
		$schoolId = (int)$this->_oCombination->items_school;

		if(!empty($schoolId)) {
			$school = \Ext_Thebing_School::getInstance($schoolId);
			$this->_assign('school', $school);
		}

		$accommodationCategory = \Ext_Thebing_Accommodation_Category::getInstance($this->_oCombination->items_accommodation_category);
		
		$accommodationCategoryProxy = \Ts\Proxy\Accommodation\Category::getInstance($accommodationCategory);
		$accommodationCategoryProxy->setLanguage($language);
		
		$this->_assign('language', $language);
		$this->_assign('accommodationCategory', $accommodationCategoryProxy);
		
	}
	
}