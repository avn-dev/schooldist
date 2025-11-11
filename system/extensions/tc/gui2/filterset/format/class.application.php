<?php

class Ext_TC_Gui2_Filterset_Format_Application extends Ext_Gui2_View_Format_Abstract {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$aApplication = Ext_TC_Factory::executeStatic('Ext_TC_Gui2_Filterset', 'getApplications');		
		$oFormat = new Ext_Gui2_View_Format_Selection($aApplication);
		
		$mReturn = $oFormat->format($mValue, $oColumn, $aResultData);
		
		return $mReturn;
	}
	
}
