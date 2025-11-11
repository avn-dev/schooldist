<?php

class Ext_Thebing_Gui2_Format_Contract_Type extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		$aType 		= Ext_Thebing_Contract_Template::getTypeArray();
		
		return $aType[$mValue];

	}

}
