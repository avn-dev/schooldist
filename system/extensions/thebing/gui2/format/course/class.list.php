<?php


class Ext_Thebing_Gui2_Format_Course_List extends Ext_Gui2_View_Format_Abstract {


	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aValue = explode(',', $mValue);

		$mValue = '';

		foreach((array)$aValue as $iCourseId){
			$oCourse = Ext_Thebing_Tuition_Course::getInstance($iCourseId);
			$mValue .= $oCourse->name_short;
			$mValue .= ', ';
		}

		$mValue = rtrim($mValue, ', ');

		return $mValue;
	}

}
