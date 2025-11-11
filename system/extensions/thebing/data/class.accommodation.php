<?php

class Ext_Thebing_Data_Accommodation {

	public static function getAccommodations($bForSelects = true){

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aAccommodations = $oSchool->getTransferLocations($bForSelects);

		if($bForSelects) {
			asort($aAccommodations);
		}

		return $aAccommodations;
	}

	public static function getAccommodationRoomTypes($bForSelect = true, $sDefaultLang = false, $bCheckValid=true) {

		if(!$sDefaultLang) {
			$sDefaultLang = Ext_Thebing_Util::getInterfaceLanguage();
		}

		$sNameField = 'name_'.$sDefaultLang;
				
		$oRoomtype = new Ext_Thebing_Accommodation_Roomtype();
		$aArrayList = $oRoomtype->getArrayListJoinedSchool($bForSelect, $sNameField);
		
		return $aArrayList;
	}

	public static function getAccommodationMeals($bForSelect = true, $sDefaultLang = false){

		if(!$sDefaultLang) {
			$sDefaultLang = Ext_Thebing_Util::getInterfaceLanguage();
		}

		$sNameField = 'name_'.$sDefaultLang;
				
		$oMeal = new Ext_Thebing_Accommodation_Meal();
		$aArrayList = $oMeal->getArrayListJoinedSchool($bForSelect, $sNameField);
		
		return $aArrayList;
		
	}

	public static function getAccommodationCategories($bForSelect = true, $sDefaultLang = false) {

		if(!$sDefaultLang) {
			$sDefaultLang = Ext_Thebing_Util::getInterfaceLanguage();
		}
		
		$school = Ext_Thebing_School::getSchoolFromSession();
		$aArrayList = $school->getAccommodationCategoriesList($bForSelect);
		
//		$oCategory = new Ext_Thebing_Accommodation_Category();
//		$aArrayList = $oCategory->getArrayListJoinedSchool($bForSelect, $sNameField);
		
		return $aArrayList;
	}
	
}
