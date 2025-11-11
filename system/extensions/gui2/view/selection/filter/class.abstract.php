<?php

abstract class Ext_Gui2_View_Selection_Filter_Abstract implements Ext_Gui2_View_Selection_Filter_Interface {
	
	/**
	 * gibt die Select-Options für einen Filter zurück
	 * @param array $aParentGuiIds
	 * @param Ext_Gui2 $oGui
	 * @return array 
	 */
	public function getOptions($aParentGuiIds, &$oGui) {
		$aOptions = array();
		return $aOptions;		
	}
	
}
