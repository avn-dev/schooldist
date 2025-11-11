<?php

namespace TsCompany\Gui2\Selection;

use TsCompany\Entity\Company;
use TsCompany\Entity\Contact;

class CompanyContacts extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aAgencyContacts = array();

		$iAgencyId			= (int)$oWDBasic->company_id;

		if($iAgencyId > 0) {
			$oAgency			= Company::getInstance($iAgencyId);
			$aAgencyContacts	= $oAgency->getContacts(true);
			$aAgencyContacts	= \Ext_Thebing_Util::addEmptyItem($aAgencyContacts);
			$iContact			= $oWDBasic->company_contact_id;
			$oContact			= Contact::getInstance($iContact);

			if($oContact->company_id != $iAgencyId){
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
