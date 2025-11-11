<?php

class Ext_Thebing_Transfer_Package_Gui2_Selection_Provider extends Ext_Gui2_View_Selection_Abstract {

	public function  __construct() {

	}

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aProviders = $oSchool->getTransferProvider(true);

		return $aProviders;
		
	}

}