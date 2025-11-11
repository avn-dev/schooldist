<?php

class Ext_Thebing_Gui2_Format_Holiday_Schoollist extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oPublicHoliday = Ext_Thebing_Holiday_Holiday::getInstance($mValue);

		$aSchools = Ext_Thebing_Util::convertDataIntoObject($oPublicHoliday->join_school, 'Ext_Thebing_School');

		$aBack = array();
		foreach((array)$aSchools as $oSchool){
			$aBack[] = $oSchool->ext_1;
		}
		return implode('<br/>', $aBack);
	}

}
