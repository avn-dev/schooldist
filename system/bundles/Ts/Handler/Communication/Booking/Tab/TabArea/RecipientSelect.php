<?php

namespace Ts\Handler\Communication\Booking\Tab\TabArea;

/**
 * @deprecated
 */
class RecipientSelect extends \Ext_TC_Communication_Tab_TabArea_RecipientSelect {

	protected function setTitle() {

		$sIcon = $this->getTitleIcon();
		$sTitle = \L10N::t('Schüler');

		$this->sTitle = $sIcon.'&nbsp;'.$sTitle;
	}

	public function getRecipients() {

		$aRecipients = array();
		$aSelectedIds = $this->_oParent->getParentTab()->getCommunicationObject()->getSelectedIds();
		$sType = $this->_oParent->getType();

		foreach($aSelectedIds as $iSelectedId) {

			if($sType === 'customer') {
				$aContacts = $this->getCustomers($iSelectedId);
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
		/* @var $oObject \Ext_TS_Inquiry */

		$aContacts = $oObject->getTravellers();

		$aRecipients = $this->getBaseContactArray($aContacts, $iSelectedId);

		return $aRecipients;
		
	}
	
	public function getObjectofContactType($sType) {
		return '\Ext_TS_Inquiry_Contact_Traveller';
	}
			
			
	
}
