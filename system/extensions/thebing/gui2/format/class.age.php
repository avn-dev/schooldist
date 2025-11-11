<?php

class Ext_Thebing_Gui2_Format_Age extends Ext_Thebing_Gui2_Format_Int {

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		
		if(is_numeric($mValue)) {		
			$mValue = Ext_Thebing_Format::Int($mValue, null, (int)$aResultData['school_id']);
		} else {
			$mValue = '';
		}

		return $mValue;

	}
	
}