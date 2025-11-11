<?php

class Ext_Thebing_Gui2_Selection_School_Status extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @inheritdocs
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

    	/** @var Ext_TS_Enquiry $oWDBasic */
		$oSchool = $oWDBasic->getSchool();
		
		$aStatus = $oSchool->getCustomerStatusList();
		$aStatus = Ext_Thebing_Util::addEmptyItem($aStatus);

		return $aStatus;

	}

}
