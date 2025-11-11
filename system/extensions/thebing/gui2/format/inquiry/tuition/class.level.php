<?php

class Ext_Thebing_Gui2_Format_Inquiry_Tuition_Level extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		if(empty($mValue)) {
			return '';
		}
		if(!is_array($mValue)) {
			$mValue = explode(',', $mValue);
		}
		$sLanguage = Ext_TC_System::getInterfaceLanguage();
		$aLevels = array_map(
			function($iLevelId) use ($sLanguage) {
				$oLevel = Ext_Thebing_Tuition_Level::getInstance($iLevelId);
				return $oLevel->getName($sLanguage);
			},
			$mValue
		);
		return implode(', ', $aLevels);
	}

}
