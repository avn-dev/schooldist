<?php

abstract class Ext_TC_Frontend_Form_Field_Select_Selection extends Ext_Gui2_View_Selection_Abstract {
	
	/**
	 * @var array 
	 */
	protected $aSelectionSettings = [];
	
	public function setSelectionSettings(array $aSelectionSettings) {
		$this->aSelectionSettings = $aSelectionSettings;
	}
	
	public function initialize() { }
	
	public function getGroupedOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		return [];
	}
}

