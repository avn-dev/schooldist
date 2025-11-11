<?php

namespace Office\Gui2\Selection;

class CustomerContactSelection extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$aReturn = array();

		$oExtensionDaoOffice = new \Ext_Office_Dao();

		$aContacts = $oExtensionDaoOffice->getContacts($oWDBasic->customer_id);

		foreach($aContacts as $aContact) {
			$aReturn[$aContact['id']] = $aContact['lastname'].', '.$aContact['firstname'];
		}

		return $aReturn;

	}

}