<?php


class Ext_TS_Tuition_Attendance_Gui2_Format_Template extends Ext_TC_Gui2_Format
{	
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$oTemplate = new Ext_Thebing_Tuition_Template();
		
		$oTemplate->name	= $aResultData['template_name'];
		$oTemplate->from	= $aResultData['template_from'];
		$oTemplate->until	= $aResultData['template_until'];
		$oTemplate->custom	= $aResultData['template_custom'];
		
		$sName = $oTemplate->getNameAndTime();
		
		return $sName;
	}
}