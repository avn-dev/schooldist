<?php

class Ext_Thebing_Gui2_Format_Birthday extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(empty($aResultData['school_id'])){
			$aResultData['school_id'] = Ext_Thebing_School::getSchoolFromSession()->id;
		}

		return Ext_Thebing_Format::LocalDate($mValue, $aResultData['school_id']);

	}

}
