<?php

class Ext_Thebing_Gui2_Format_Statistic_Type extends Ext_Gui2_View_Format_Abstract
{
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$aTypes = Ext_Thebing_Management_Statistic::getTypes();

		$sType = $aTypes[$mValue];

		if($mValue == 1) // relativ, add interval
		{
			$aIntervals = Ext_Thebing_Management_Statistic::getIntervals();

			$sType .= ' (' . $aIntervals[$aResultData['interval']] . ')';
		}

		return $sType;
	}
}