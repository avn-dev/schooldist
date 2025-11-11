<?php

class Ext_Thebing_Gui2_Selection_Examination_Course extends Ext_Gui2_View_Selection_Abstract
{

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
		$iInquiryId = $oWDBasic->inquiry_id;
		$aCourses = Ext_Thebing_Examination_Version_Gui2::getCourseArrayList($iInquiryId);

		return $aCourses;
	}

}
