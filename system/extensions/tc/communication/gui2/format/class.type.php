<?php

class Ext_TC_Communication_Gui2_Format_Type extends Ext_Gui2_View_Format_Abstract {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$sValue = '';
		$aTypes = Ext_TC_Factory::executeStatic('Ext_TC_Communication_Gui2_Data', 'getTypes');

		if(!empty($aTypes[$mValue])) {
			$sValue = $aTypes[$mValue];
		}

		return $sValue;
		
	}
	
}
