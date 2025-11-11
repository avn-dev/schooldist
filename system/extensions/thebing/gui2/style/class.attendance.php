<?php


class Ext_Thebing_Gui2_Style_Attendance extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aResultData){
		
		$iSchoolId = (int)$aResultData['school_id'];

		if($iSchoolId == 0) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		} else {
			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
		}

		$iCritical	= $oSchool->critical_attendance;

		if($mValue != null && $mValue <= $iCritical) {
			return 'color: ' . Ext_Thebing_Util::getColor('red_font');
		}

		return '';
	}

}