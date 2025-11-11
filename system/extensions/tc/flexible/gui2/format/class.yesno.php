<?php

class Ext_TC_Flexible_Gui2_Format_YesNo extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		$sReturn = '';

		$aYesNo = Ext_TC_Util::getYesNoArray(false);

		if(isset($aYesNo[$mValue])) {
			$sReturn = $aYesNo[$mValue];
		}

		return $sReturn;
	}
	
}