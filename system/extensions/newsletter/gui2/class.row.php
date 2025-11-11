<?php

class Ext_Newsletter_Gui2_Row extends Ext_Gui2_View_Style_Abstract
{
	public function getStyle($mValue, &$oColumn, &$aRowData)
	{
		if($aRowData['active'] != 1)
		{
			return 'color: #777;';
		}
	}
}
