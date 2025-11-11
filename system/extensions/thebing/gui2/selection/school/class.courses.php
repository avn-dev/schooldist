<?php

class Ext_Thebing_Gui2_Selection_School_Courses extends Ext_Thebing_Gui2_Selection_School_Abstract {
	
	protected function getSchoolOptions() {

		$aOptions = $this->oSchool->getCourseList();
		
		return $aOptions;
	}

}
