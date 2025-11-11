<?php

class Ext_TS_Gui2_Format_DateTime extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		$sReturn = '';

		$oDate = new Ext_Thebing_Gui2_Format_Date();
		$oTime = new Ext_Thebing_Gui2_Format_Time();

		$aParts = explode('T', $mValue);

		if(
			!empty($aParts[0]) &&
			$aParts[0] !== '0000-00-00'
		) {
			$sReturn .= $oDate->format($aParts[0]);
		}

		if(
			!empty($aParts[1]) &&
			$aParts[1] !== '00:00'
		) {
			$sReturn .= ' '.$oTime->format($aParts[1]);
		}

		return $sReturn;
	}

}
