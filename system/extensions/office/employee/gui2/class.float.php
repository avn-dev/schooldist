<?php

class Ext_Office_Employee_Gui2_Float extends Ext_Gui2_View_Format_Float {
	
	public function format($fAmount, &$oColumn = null, &$aResultData = null) {
		
		$sAmount = parent::format($fAmount, $oColumn, $aResultData);
		
		$sAmount = $sAmount . " €";
		
		return $sAmount;
		
	}
	
}
