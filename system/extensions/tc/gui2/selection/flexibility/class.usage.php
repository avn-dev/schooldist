<?php

class Ext_TC_Gui2_Selection_Flexibility_Usage extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$aOptions = array();
		
		$section = $oWDBasic->getSection();

		$categoryUsage = Ext_TC_Factory::executeStatic('Ext_TC_Flexibility', 'getCategoryUsage', [$this->_oGui]);

		if(
			$section instanceof Ext_TC_Flexible_Section &&
			isset($categoryUsage[$section->category])
		) {
			$aOptions = $categoryUsage[$section->category];
		}

		return $aOptions;
	}

}