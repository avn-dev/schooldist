<?php

/**
 * Dient als Interface
 */
class Ext_TC_Communication_Tab_TabArea_RecipientSelect {

	/**
	 * @var Ext_TC_Communication_Tab_TabArea 
	 */
	protected $_oParent;
	public $sTitle = '';
	
	public function __construct(Ext_TC_Communication_Tab_TabArea &$oParent) {
		
		$this->_oParent = $oParent;
		$this->setTitle();
		
	}

	protected function setTitle()
	{

		throw new Exception('Please overwrite setTitle()!');

	}

	public function getRecipients() {
		
		throw new Exception('Please overwrite getRecipients()!');
		
	}

	protected function getTitleIcon()
	{
		$sTranslation = Ext_TC_Communication::t('Addressbuch');

		$sType = $this->_oParent->getParentTab()->getType();

		if($sType == 'sms') {
			$sIconClass = 'fas fa-mobile-alt';
		} else {
			$sIconClass = 'fas fa-address-book';
		}

		$sIcon = '<i style="font-size: 15px" class="'.$sIconClass.'" title="'.$sTranslation.'"></i>';

		return $sIcon;

	}

	protected function _encodeRecipients(array $aRecipients) {
		
		$oDialogData = $this->_oParent->getParentTab()->getCommunicationObject()->getDialogObject()->getDataObject();

		$aRecipientCache = &$oDialogData->aRecipientCache[$this->_oParent->getType()];

		$iCounter = 0;
		if(!empty($aRecipientCache)) {
			$iCounter = count($aRecipientCache);
		}
				
		$iCounter++;

		$aReturn = array();
		foreach($aRecipients as $aRecipient) {

			// Empfänger mit leeren Adressen überspringen
			// Redmine Ticket #2745
			if($aRecipient['address'] === '') {
				continue;
			}

			$sJson = json_encode($aRecipient);
			
			$iCrc = crc32($sJson);
			
			// Prüfen, ob Empfänger schon im Cache ist
			$bInCache = false;
			$iNewKey = null;
			if(is_array($aRecipientCache)) {
				foreach($aRecipientCache as $iKey=>$aEntry) {
					if($aEntry['crc'] == $iCrc) {
						$bInCache = true;
						$iNewKey = $iKey;
						break;
					}
				}
			}

			// Wenn Empfänger noch nicht im Cache ist, dann einfügen
			if($bInCache !== true) {
				$iNewKey = $iCounter;
				$aRecipient['crc'] = $iCrc;
				$aRecipientCache[$iNewKey] = $aRecipient;
				$iCounter++;
			}

			// TODO: Korrekt umsetzen
			//$aRecipient['selected_ids'] = $this->_oParent->getParentTab()->getCommunicationObject()->getSelectedIds();

			$sName = $this->_formatRecipientName($aRecipient);
			$aReturn[$iNewKey] = $sName;

		}

		return $aReturn;
		
	}

	protected function _formatRecipientName(array $aRecipient)
	{
		$sName = $aRecipient['name'].' ('.$aRecipient['address'].')';
		return $sName;
	}
	
	/**
	 * Liefert den Basisaufbau des Kontaktarrays
	 *
	 * @param array $aContacts
	 * @param int $iSelectedId
	 * @return array
	 */
	public function getBaseContactArray(array $aContacts, $iSelectedId) {

		$aRecipients = array();

		$sType = $this->_oParent->getParentTab()->getType();

		foreach($aContacts as $oContact) {
			/* @var $oContact Ext_TC_Contact */

			if($sType == 'sms') {
				$aItems = $oContact->getMobileNumbers();
			} elseif($sType == 'app') {
				$aItems = $oContact->getMobileNumbers();
			} else {
				$aItems = $oContact->getEmailAddresses();
			}

			foreach($aItems as $mItem) {

				try {
					$sContactType = $oContact->type;
				} catch(Exception $e) {
					$sContactType = '';
				}
				
				if($sType === 'sms') {
					$mTo = $mItem;
				} elseif($sType === 'app') {
					$mTo = $mItem;
				} else {
					if (is_string($mItem)) {
						$mTo = $mItem;
					} else {
						$mTo = $mItem->email;
					}

				}

				$sObject = $this->getObjectofContactType($sContactType, $oContact);

				$aRecipients[] = array(
					'name' => $oContact->getName(),
					'address' => $mTo,
					'object_id' => $oContact->id,
					'object' => $sObject,
					'selected_id' => $iSelectedId
				);
				
			}
		}
		
		return $aRecipients;
	}
	
}
