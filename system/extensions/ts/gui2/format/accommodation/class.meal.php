<?php

class Ext_TS_Gui2_Format_Accommodation_Meal extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$iMealId = (int)$mValue;
		$oMeal = Ext_Thebing_Accommodation_Meal::getInstance($iMealId);
		
		$sValue = $oMeal->getName();
		return $sValue;

	}

}
