<?php

/**
 *   Selection Klasse für Objekte
 */

class Ext_TC_Frontend_Extrafield_Format_Type extends Ext_TC_Gui2_Format { 

    public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aFieldList = Ext_TC_Extrafield::getFieldList();
		
		$sReturn = $aFieldList[$mValue];
		
		return $sReturn;

	}
	
}
