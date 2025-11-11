<?php

class Ext_TS_Enquiry_Combination_Gui2_Selection_Accommodation_Meal extends Ext_Gui2_View_Selection_Abstract {

    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		$aReturn = array();
		$oSchool = $oWDBasic->getSchool();

		if(
			$this->oJoinedObject &&
			$oSchool
		) {
			$aMeals = $oSchool->getAccommodationMealCombinations();
			$oAccommodation = $this->oJoinedObject;

			if(!empty($aMeals[$oAccommodation->accommodation_id])) {
				$aTemp = $aMeals[$oAccommodation->accommodation_id];
				foreach($aTemp as $iRoomId => $aMealData) {
					foreach($aMealData as $iMealId) {
						$oMeal = Ext_Thebing_Accommodation_Meal::getInstance($iMealId);
						$aReturn[$iMealId] = $oMeal->getName();
					}
				}
			}

		}

		return $aReturn;
	}

}