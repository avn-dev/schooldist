<?php

class Ext_TC_Gui2_Format_Percent extends Ext_TC_Gui2_Format_Float{
	
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$mValue = parent::format($mValue, $oColumn, $aResultData);

		$mValue .= ' %';

		return $mValue;
	}

}