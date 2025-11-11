<?php

class Ext_Thebing_Gui2_Format_Inquiry_Course_Level extends Ext_Gui2_View_Format_Abstract
{	

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		$oSchool		= Ext_Thebing_Client::getFirstSchool();
		$aLevels 		= $oSchool->getCourseLevelList();
		$sReturn		= $aLevels[$mValue];
		return $sReturn;
	}
	
}