<?php

/**
 *  Format-Klasse für Zwischenkategorien
 */

class Ext_TC_Accounting_Format_Category extends Ext_Gui2_View_Format_Abstract {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$aCategories = Ext_TC_Accounting_Category::getSelectOptions();
		
		if(array_key_exists($mValue, $aCategories)){
			$sCategory = $aCategories[$mValue];
		}
		
		return $sCategory;
		
	}
	
}