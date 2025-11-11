<?php

class Ext_TS_Frontend_Combination_Pricelist extends Ext_TC_Frontend_Combination_Abstract {

	/**
	 * {@inheritdoc}
	 */
	protected function _default() {

		$sLanguage = $this->_oCombination->items_language;
		
		$iSchoolId = $this->_oCombination->items_school;
		$oSchool = Ext_Thebing_School::getInstance($iSchoolId);

		$oSchoolProxy = new Ext_Thebing_School_Proxy($oSchool);
		$aAccommodationCombinationsComplete = $oSchool->getAccommodationCombinations($sLanguage);

		$aSeasonIds = $this->_oCombination->items_seasons;
		$aCourseIds = $this->_oCombination->items_courses;
		$aAccommodationCombinationIds = $this->_oCombination->items_accommodationcombinations;

		$aSeasons = [];
		foreach($aSeasonIds as $iSeasonId) {
			$oSeasonProxy = new \Ts\Proxy\Season(Ext_Thebing_Marketing_Saison::getInstance($iSeasonId));
			$oSeasonProxy->setLanguage($sLanguage);
			$aSeasons[] = $oSeasonProxy;
		}

		$aCourses = [];
		foreach($aCourseIds as $iCourseId) {
			$oCourseProxy = new Ts\Proxy\Course(Ext_Thebing_Tuition_Course::getInstance($iCourseId));
			$oCourseProxy->setLanguage($sLanguage);
			$aCourses[] = $oCourseProxy;
		}

		$aAccommodationCombinations = [];
		
		foreach($aAccommodationCombinationIds as $sAccommodationCombinationId) {

			list(
				$iAccommodationCategoryId,
				$iRoomtypeId,
				$iMealId
			) = explode('_', $sAccommodationCombinationId);
			
			$oAccommodationCombinationProxy = new Ts\Proxy\Accommodation\Combination(Ext_Thebing_Accommodation_Category::getInstance($iAccommodationCategoryId));
			$oAccommodationCombinationProxy->setLanguage($sLanguage);
			$oAccommodationCombinationProxy->setKey($sAccommodationCombinationId);
			$oAccommodationCombinationProxy->setLabel($aAccommodationCombinationsComplete[$sAccommodationCombinationId]);
			$oAccommodationCombinationProxy->setRoomtype(Ext_Thebing_Accommodation_Roomtype::getInstance($iRoomtypeId));
			$oAccommodationCombinationProxy->setMeal(Ext_Thebing_Accommodation_Meal::getInstance($iMealId));
			
			$aAccommodationCombinations[] = $oAccommodationCombinationProxy;
		}

		$this->_assign('oNow', new \Carbon\Carbon());
		$this->_assign('oSchool', $oSchoolProxy);
		$this->_assign('aSeasons', $aSeasons);
		$this->_assign('aCourses', $aCourses);
		$this->_assign('aAccommodationCombinations', $aAccommodationCombinations);

	}

}
