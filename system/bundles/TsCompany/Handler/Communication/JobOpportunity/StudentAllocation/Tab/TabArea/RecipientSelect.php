<?php

namespace TsCompany\Handler\Communication\JobOpportunity\StudentAllocation\Tab\TabArea;

use TsCompany\Entity\JobOpportunity\StudentAllocation;

/**
 * @deprecated
 */
class RecipientSelect extends \Ext_TC_Communication_Tab_TabArea_RecipientSelect {

	private $sRecipientType = null;

	public function __construct(\Ext_TC_Communication_Tab_TabArea &$oParent, string $sRecipientType = null) {
		$this->sRecipientType = $sRecipientType;
		parent::__construct($oParent);
	}

	protected function setTitle() {

		$sType = $this->_oParent->getType();

		$sIcon = $this->getTitleIcon();
		$sTitle = '';

		if ($sType === 'customer') {

			if ($this->sRecipientType === 'agency_contacts') {
				$sTitle = \L10N::t('Agenturkontakte');
			} else {
				$sTitle = \L10N::t('Schüler');
			}

		} else if ($sType === 'company') {
			$sTitle = \L10N::t('Firmenkontakte');
		}

		$this->sTitle = $sIcon.'&nbsp;'.$sTitle;
	}

	public function getRecipients() {

		$aRecipients = array();
		$aSelectedIds = $this->_oParent->getParentTab()->getCommunicationObject()->getSelectedIds();
		$sType = $this->_oParent->getType();

		foreach($aSelectedIds as $iSelectedId) {

			if ($sType === 'customer') {
				$aContacts = $this->getCustomers($iSelectedId);
			} else if ($sType === 'company') {
				$aContacts = $this->getCompanyContacts($iSelectedId);
			} else {
				continue;
			}

			// Doppelte Kontakte vermeiden
			foreach($aContacts as $aContact) {
				$bFound = false;
				foreach($aRecipients as $aRecipient) {

					// Arrays dürfen wegen Selected Id nicht direkt miteinander verglichen werden!
					if(
						$aRecipient['name'] === $aContact['name'] &&
						$aRecipient['address'] === $aContact['address'] &&
						$aRecipient['object'] === $aContact['object'] &&
						$aRecipient['object_id'] === $aContact['object_id']
					) {
						$bFound = true;
						break;
					}
				}
				if(!$bFound) {
					$aRecipients[] = $aContact;
				}
			}

		}

		$aReturn = $this->_encodeRecipients($aRecipients);

		return $aReturn;

	}
	
	public function getCustomers($iSelectedId) {

		$sObjectClass = $this->_oParent->getParentTab()->getCommunicationObject()->getObjectClassName();
		$oObject = \Ext_TC_Factory::getInstance($sObjectClass, $iSelectedId);
		/* @var $oObject StudentAllocation */

		$aRecipients = [];

		if ($this->sRecipientType === 'agency_contacts') {

			$oAgency = $oObject->getInquiry()->getAgency();

			if ($oAgency) {
				/* @var \TsCompany\Entity\Contact[] $aContacts */
				$aContacts = $oAgency->getContacts(false, true);
				$aRecipients = $this->buildCompanyContactArray($oAgency, $aContacts, $iSelectedId);
			}

		} else {

			$aContacts = $oObject->getInquiry()->getTravellers();
			$aRecipients = $this->getBaseContactArray($aContacts, $iSelectedId);

		}

		return $aRecipients;
		
	}

	public function getCompanyContacts($iSelectedId) {

		$sObjectClass = $this->_oParent->getParentTab()->getCommunicationObject()->getObjectClassName();
		$oObject = \Ext_TC_Factory::getInstance($sObjectClass, $iSelectedId);
		/* @var $oObject StudentAllocation */

		$oCompany = $oObject->getJobOpportunity()->getCompany();

		$aContacts = $oCompany->getContacts();

		$aRecipients = $this->buildCompanyContactArray($oCompany, $aContacts, $iSelectedId);

		return $aRecipients;

	}

	/**
	 * Da die Firmenkontakte nicht auf den TcContacts basieren muss das Array selbst erstellt werden (siehe getBaseContactArray()
	 *
	 * @param \TsCompany\Entity\AbstractCompany $oCompany
	 * @param $aContacts
	 * @param $iSelectedId
	 * @return array
	 */
	private function buildCompanyContactArray(\TsCompany\Entity\AbstractCompany $oCompany, $aContacts, $iSelectedId): array {

		$aRecipients = [];
		foreach ($aContacts as $oAgencyContact) {

			if (empty($oAgencyContact->email)) {
				continue;
			}

			$aRecipients[] = array(
				'name' => sprintf('%s: %s', $oCompany->getName(), $oAgencyContact->getName()),
				'address' => $oAgencyContact->email,
				'object_id' => $oAgencyContact->id,
				'object' => get_class($oAgencyContact),
				'selected_id' => $iSelectedId
			);

		}

		return $aRecipients;

	}

	public function getObjectofContactType($sType) {
		return '\Ext_TS_Inquiry_Contact_Traveller';
	}

}
