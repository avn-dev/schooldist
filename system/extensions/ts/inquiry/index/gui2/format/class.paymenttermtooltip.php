<?php

use \Illuminate\Support\Str;

class Ext_TS_Inquiry_Index_Gui2_Format_PaymentTermTooltip extends Ext_Gui2_View_Format_ToolTip {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		$sInfo = parent::format($mValue, $oColumn, $aResultData);
		$sInfo = Str::before($sInfo, "\n");
		return $sInfo;
	}

	public function getTitle(&$oColumn = null, &$aResultData = null) {
		$aTooltip = parent::getTitle($oColumn, $aResultData);
		$aTooltip['content'] = Str::after($aTooltip['content'], "\n");
		return $aTooltip;
	}

	public function align(&$oColumn = null) {
		return 'right';
	}

}
