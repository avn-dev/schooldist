<?php

class Ext_Thebing_Gui2_Format_Statistic_Period extends Ext_Gui2_View_Format_Abstract
{
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$aPeriods = Ext_Thebing_Management_Statistic::getPeriods();

		return $aPeriods[$mValue];
	}
}