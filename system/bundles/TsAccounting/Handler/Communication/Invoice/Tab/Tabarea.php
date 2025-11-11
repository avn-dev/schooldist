<?php

namespace TsAccounting\Handler\Communication\Invoice\Tab;

/**
 * @deprecated
 */
class Tabarea extends \Ext_TC_Communication_Tab_TabArea {

	public function getRecipientSelects() {

		$sClassName = $this->_oParent->getCommunicationObject()->getClassName('Tab_TabArea_RecipientSelect');

		$oSelect = new $sClassName($this);

		$sType = $this->_sType;

		if($sType == 'customer') {
			$oSelect->sTitle = 'Kunden';
			$oSelect->sKey = 'customer';
		}

		$aSelects = array($oSelect);

		return $aSelects;

	}

	public function createRecipientFields() {

		if($this->getParentTab()->getType() === 'app') {
			return;
		}

		$oContainer = parent::createRecipientFields();
		
		return $oContainer;
	}
	
	public function checkPossibleRecipients() {

		// Nur bei App kann man nicht manuell Empfänger eingeben
		if($this->getParentTab()->getType() === 'app') {
			
			$recipients = [
				'to' => []
			];
			$this->getParentTab()->getCommunicationObject()->addStaticRecipients($this, $recipients);
			
			if(empty($recipients['to'])) {
				return false;
			}
			
		}
		
		return true;
	}
	
}
