<?php

class Ext_Thebing_Gui2_Selection_School_Accommodations extends Ext_Thebing_Gui2_Selection_School_Abstract {
	
	protected function getSchoolOptions() {

		$aOptions = $this->oSchool->getAccommodationCombinations();
		
		return $aOptions;
	}

}
