<?php

class Ext_TC_Gui2_Selection_Country extends Ext_Gui2_View_Selection_Abstract {

	protected $scope;

	public function __construct(string $scope = null) {
		$this->scope = $scope;
	}
	
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		
		$aSelection = Ext_TC_Country::getSelectOptions();
		return $aSelection;

	}
	
}
?>
