<?php

class Ext_Thebing_Gui2_Selection_School_CostWeek extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * {@inheritdoc}
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aSelectedSchoolIds = [];

		if($oWDBasic instanceof Ext_Thebing_Accommodation_Cost_Category) {
			$aSelectedSchoolIds = $oWDBasic->schools;
		}

		return Ext_Thebing_Accommodation_Cost_Week::getListForSchools($aSelectedSchoolIds, true);

	}

}
