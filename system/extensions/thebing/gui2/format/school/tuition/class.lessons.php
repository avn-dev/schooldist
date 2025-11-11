<?php

class Ext_Thebing_Gui2_Format_School_Tuition_Lessons extends Ext_Thebing_Gui2_Format_Float {

	public function  __construct() {
		parent::__construct(2, false);
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$iRemainingLessons = $aResultData['course_lessons'] - $aResultData['allocation_lessons'];

		return parent::format($iRemainingLessons,$oColumn,$aResultData);
	}
}