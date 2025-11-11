<?php

class Ext_Thebing_Gui2_Style_School_Tuition_Until extends Ext_Gui2_View_Style_Abstract
{

	public function getStyle($mValue, &$oColumn, &$aRowData)
	{
		if(1==$aRowData['last_week'])
		{
			$sColorYellow = Ext_Thebing_Util::getColor('yellow');
			return 'background:'.$sColorYellow;
		}

		return null;
	}

}

?>
