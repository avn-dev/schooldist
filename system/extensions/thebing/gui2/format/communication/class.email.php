<?php

class Ext_Thebing_Gui2_Format_Communication_Email extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sReturn = '<a href="" onclick="return false;">'.$mValue.'</a>';

		return $sReturn;

	}

}
