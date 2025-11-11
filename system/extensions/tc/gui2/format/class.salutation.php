<?php

class Ext_TC_GUI2_Format_Salutation extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aSelection = Ext_TC_Util::getPersonTitles();

		$mValue = (string)$aSelection[$mValue];

		return $mValue;

	}

}
