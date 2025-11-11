<?php

namespace TsAccounting\Handler\Communication\Invoice\Tab\TabArea;

/**
 * @deprecated
 */
class RecipientSelect extends \Ext_TC_Communication_Tab_TabArea_RecipientSelect {
	
	protected function setTitle() {

		$sType = $this->_oParent->getType();

		$sTitle = match ($sType) {
			'customer' => \L10N::t('Schüler'),
			'agency', 'sponsor' => \L10N::t('Kontakte'),
			'group' => \L10N::t('Gruppenleiter'),
			'default' => throw new \RuntimeException(sprintf('Unknown type "%s" for recipient select [%s]', $sType, __METHOD__))
		};

		$this->sTitle = sprintf('%s %s ', $this->getTitleIcon(), $sTitle);
	}

	public function getRecipients() {

		$aRecipients = array();
		$aSelectedIds = $this->_oParent->getParentTab()->getCommunicationObject()->getSelectedIds();
		$sType = $this->_oParent->getType();

		foreach($aSelectedIds as $iSelectedId) {

			$sObjectClass = $this->_oParent->getParentTab()->getCommunicationObject()->getObjectClassName();
			$oObject = \Ext_TC_Factory::getInstance($sObjectClass, $iSelectedId);
			/* @var $oObject \Ext_Thebing_Inquiry_Document */

			if ($sType === 'customer') {
				$aContacts = $this->getCustomers($oObject);
			} else if ($sType === 'agency') {
				$aContacts = $this->getAgencyContacts($oObject);
			} else if ($sType === 'sponsor') {
				$aContacts = $this->getSponsorContacts($oObject);
			} else if ($sType === 'group') {
				$aContacts = $this->getGroupContacts($oObject);
			}  else {
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
	
	public function getCustomers(\Ext_Thebing_Inquiry_Document $document) {
		$inquiry = $document->getInquiry();
		$contact = $inquiry->getTraveller();

		$recipients = $this->getBaseContactArray([$contact], $document->id);

		return $recipients;
	}

	public function getAgencyContacts(\Ext_Thebing_Inquiry_Document $document) {
		$inquiry = $document->getInquiry();

		if (!$inquiry->hasAgency()) {
			return [];
		}

		$agencyContact = $inquiry->getAgencyContact();

		if (!$agencyContact->exist()) {
			$contacts = $inquiry->getAgency()->getContacts(bAsObjects: true);
		} else {
			$contacts = [$agencyContact];
		}

		$recipients = $this->getBaseContactArray($contacts, $document->id);

		return $recipients;
	}

	public function getSponsorContacts(\Ext_Thebing_Inquiry_Document $document) {
		$inquiry = $document->getInquiry();

		if (!$inquiry->isSponsored()) {
			return [];
		}

		$contacts = $inquiry->getSponsorContactsWithValidEmails();

		$recipients = $this->getBaseContactArray($contacts, $document->id);

		return $recipients;
	}

	public function getGroupContacts(\Ext_Thebing_Inquiry_Document $document) {
		$inquiry = $document->getInquiry();

		if (!$inquiry->hasGroup()) {
			return [];
		}

		$group = $inquiry->getGroup();

		$contact = $group->getContactPerson();
		$guides = array_map(fn (\Ext_TS_Inquiry $guideInquiry) => $guideInquiry->getTraveller(), $group->getGuides());

		$recipients = $this->getBaseContactArray(array_merge([$contact], $guides), $document->id);

		return $recipients;
	}

	public function getObjectofContactType($sType, $oContact) {
		return $oContact::class;
	}

}
