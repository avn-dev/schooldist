<?php

namespace TsTuition\Handler\Communication\Allocation\Tab\TabArea;

/**
 * @deprecated
 */
class RecipientSelect extends \Ext_TC_Communication_Tab_TabArea_RecipientSelect {
	
	protected function setTitle() {
			
		$sIcon = $this->getTitleIcon();

		$sType = $this->_oParent->getType();

		$sTitle = '';
		if ($sType === 'customer') {
			$sTitle = \L10N::t('Schüler');
		} else if ($sType === 'teacher') {
			$sTitle = \L10N::t('Lehrer');
		}

		$this->sTitle = $sIcon.' '.$sTitle;
	}

	public function getRecipients() {

		$aRecipients = array();
		$aSelectedIds = $this->_oParent->getParentTab()->getCommunicationObject()->getSelectedIds();
		$sType = $this->_oParent->getType();

		foreach($aSelectedIds as $iSelectedId) {

			if ($sType === 'customer') {
				$aContacts = $this->getCustomers($iSelectedId);
			} else if ($sType === 'teacher') {
				$aContacts = $this->getTeachers($iSelectedId);
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
		/* @var $oObject \Ext_Thebing_School_Tuition_Allocation */

		$oInquiry = $oObject->getJourneyCourse()->getJourney()->getInquiry();
		$oContact = $oInquiry->getTraveller();

		$aRecipients = $this->getBaseContactArray([$oContact], $iSelectedId);

		return $aRecipients;
		
	}

	public function getTeachers($iSelectedId) {

		$sObjectClass = $this->_oParent->getParentTab()->getCommunicationObject()->getObjectClassName();
		$oObject = \Ext_TC_Factory::getInstance($sObjectClass, $iSelectedId);
		/* @var $oObject \Ext_Thebing_School_Tuition_Allocation */

		$oBlock = $oObject->getBlock();

		$aSubsititudeTeachers = array_map(
			fn ($id) => \Ext_Thebing_Teacher::getInstance($id),
			$oBlock->getSubstituteTeachers(0, true)
		);

		$aTeachers = [...[$oBlock->getTeacher()], ...$aSubsititudeTeachers];

		$sType = $this->_oParent->getParentTab()->getType();
		$aRecipients = [];

		foreach ($aTeachers as $oTeacher) {
			/* @var \Ext_Thebing_Teacher $oTeacher */

			$aItems = [];
			if ($sType == 'email') {
				$aItems = [$oTeacher->email];
			}

			foreach ($aItems as $mValue) {
				if (empty($mValue)) {
					continue;
				}

				$aRecipients[] = array(
					'name' => $oTeacher->getName(),
					'address' => $mValue,
					'object_id' => $oTeacher->id,
					'object' => $oTeacher::class,
					'selected_id' => $iSelectedId
				);
			}

		}

		return $aRecipients;

	}

	public function getObjectofContactType($sType) {
		return match ($sType) {
			'teacher' => \Ext_Thebing_Teacher::class,
			default => \Ext_TS_Inquiry_Contact_Traveller::class,
		};
	}

}
