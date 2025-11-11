<?php

class Ext_Thebing_Gui2_Format_Statistic_ListType extends Ext_Gui2_View_Format_Abstract
{
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$aListTypes = Ext_Thebing_Management_Statistic::getListTypes();

		return $aListTypes[$mValue];
	}
}