<?php

class Ext_TC_Numberrange_Gui2_Selection_Applications extends Ext_Gui2_View_Selection_Abstract {

	protected $_sCategory;
	
	public function __construct($sCategory) {
		$this->_sCategory = $sCategory;
	}
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aApplications = Ext_TC_Factory::executeStatic('Ext_TC_NumberRange_Gui2_Data', 'getApplications');

		asort($aApplications[$this->_sCategory]);

		return $aApplications[$this->_sCategory];
	}

}