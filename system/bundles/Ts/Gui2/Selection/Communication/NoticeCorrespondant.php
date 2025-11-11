<?php

namespace Ts\Gui2\Selection\Communication;

use TsCompany\Entity\JobOpportunity\StudentAllocation;

class NoticeCorrespondant extends \Ext_TC_Communication_Message_Notice_Gui2_Selection_Correspondant {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		global $_VARS;

		// Die GUI schreibt diesen Wert in die WDBasic beim Öffnen des Dialogs
		$sParentClass = $oWDBasic->relation;
		$aParentIds = (array)$_VARS['parent_gui_id'];
		$iParentId = reset($aParentIds);

		$oParentWDBasic = $sParentClass::getInstance($iParentId);

		$aOptions = [];
		if ($oParentWDBasic instanceof \Ext_TS_Inquiry) {
			$aOptions = $this->getInquiryContactOptions($oParentWDBasic);
		} else if ($oParentWDBasic instanceof StudentAllocation) {
			$aOptions = $this->getJobOpportunityContactOptions($oParentWDBasic);
		}

		$aOptions = \Ext_TC_Util::addEmptyItem($aOptions);

		return $aOptions;
	}

	protected function getInquiryContactOptions(\Ext_TS_Inquiry $oInquiry) {

		$aOptions = [];

		$oBooker = $oInquiry->getBooker();
		$aTravellers = (array)$oInquiry->getTravellers();

		// Kunden einfügen
		#$aOptions['customer'] = \Ext_TC_Communication::t('Schüler');
		if($oBooker) {
			$aOptions['customer_contact_'.$oBooker->id] = \Ext_TC_Communication::t('Schüler').': '.$oBooker->getName();
		}
		foreach($aTravellers as $oTraveller) {
			if($oTraveller->id != $oBooker->id) {
				$aOptions['customer_contact_'.$oTraveller->id] = \Ext_TC_Communication::t('Schüler').': '.$oTraveller->getName();
			}
		}

		// Schulkontakte einfügen
		$oAgency = $oInquiry->getAgency();
		if($oAgency) {

			$aOptions['agency'] = \Ext_TC_Communication::t('Agentur').': '.$oAgency->getName();

			$oAgencyContact = $oInquiry->getAgencyContact();
			if(
				$oAgencyContact &&
				$oAgencyContact->exist()
			) {
				$aContacts = [$oAgencyContact];
			} else {
				$aContacts = $oAgency->getContacts(false, true);
			}
			foreach($aContacts as $oContact) {
				$aOptions['agency_contact_'.$oContact->id] = \Ext_TC_Communication::t('Agentur').': '.$oAgency->getName().' - '.$oContact->getName();
			}

		}

		return $aOptions;
	}

	protected function getJobOpportunityContactOptions(StudentAllocation $oStudentAllocation) {
		$oInquiry = $oStudentAllocation->getInquiry();
		return $this->getInquiryContactOptions($oInquiry);
	}

}
