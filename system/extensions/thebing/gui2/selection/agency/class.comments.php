<?php

class Ext_Thebing_Gui2_Selection_Agency_Comments extends Ext_Gui2_View_Selection_Abstract {

    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$aAgencyContacts = array();

		if($oWDBasic instanceof Ext_TS_Inquiry) {
			$iAgencyId = (int)$oWDBasic->agency_id;
			$iAgencyContactId = (int)$oWDBasic->agency_contact_id;
		} else {
			$iAgencyId = (int)$oWDBasic->company_id;
			$iAgencyContactId = (int)$oWDBasic->company_contact_id;
		}

		if($iAgencyId > 0) {
			
			$oAgency = Ext_Thebing_Agency::getInstance($iAgencyId);
			$aAgencyContacts = $oAgency->getContacts(true);
			$aAgencyContacts = Ext_Thebing_Util::addEmptyItem($aAgencyContacts);
			
			$iContact = $iAgencyContactId;
			$oContact = Ext_Thebing_Agency_Contact::getInstance($iContact);

			if($oContact->company_id != $iAgencyId) {
				$aAgencyContacts = $this->_getOptionForEmptySelected($aAgencyContacts);
			}

		}
		
		return $aAgencyContacts;
	}
	
	protected function _getOptionForEmptySelected($aOptions){
		$aNewOptions = array();
		$bSelected = true;
		foreach($aOptions as $mKey => $mValue){
			$aNewOptions[$mKey] = array('text' => $mValue, 'selected' => $bSelected);
			$bSelected = false;
		}
		return $aNewOptions;
	}
}
