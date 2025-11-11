<?php


class Ext_Thebing_Gui2_Style_School_Tuition_Lessons extends Ext_Gui2_View_Style_Abstract
{

	public function getStyle($mValue, &$oColumn, &$aRowData)
	{
		$fRemainingLessons = (float)$aRowData['remaining_lessons'];

		if($fRemainingLessons<0)
		{
			return 'color:red;';
		}

		return null;
	}

}
