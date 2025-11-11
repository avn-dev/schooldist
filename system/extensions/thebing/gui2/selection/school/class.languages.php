<?php

class Ext_Thebing_Gui2_Selection_School_Languages extends Ext_Thebing_Gui2_Selection_School_Abstract {

	protected function getSchoolOptions() {

		$aOptions = $this->oSchool->getLanguageList(true);
		
		return $aOptions;
	}

}
