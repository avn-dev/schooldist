<?php

namespace Office\Gui2\Selection;

class CustomerSelection extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$aReturn = array();

		$oExtensionDaoOffice = new \Ext_Office_Dao();
		
		$aCustomers = $oExtensionDaoOffice->getCustomers();
		
		foreach($aCustomers as $aCustomer) {
			$aReturn[$aCustomer['id']] = $aCustomer['company'];
		}
		
		return $aReturn;

	}

}