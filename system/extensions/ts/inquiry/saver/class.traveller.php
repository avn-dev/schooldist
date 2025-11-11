<?php

class Ext_TS_Inquiry_Saver_Traveller extends Ext_TS_Inquiry_Saver_Abstract {

	/**
	 * @var Ext_TS_Inquiry_Contact_Traveller
	 */
	protected $_oObject;

	/**
	 * @param Ext_TC_Basic $oObject
	 * @param string $sAlias
	 */
	public function setObject(\Ext_TC_Basic $oObject, $sAlias = '') {

		parent::setObject($oObject, $sAlias);

		$this->prepareAddresses();
		$this->prepareEmails();
		$this->prepareDetails();

	}

	protected function prepareAddresses() {
		
		// Addresse
		$oSaver = new Ext_TS_Inquiry_Saver_Basic($this->_oRequest, $this->_oGui);
		$oAddress = $this->_oObject->getAddress('contact', false);

		if($oAddress === null) {
			$isEmpty = $this->checkRequestForObjectData(['tc_a_c']);

			if(!$isEmpty) {
				$oAddress = $this->_oObject->getAddress('contact');
			}

		}

		if($oAddress !== null) {
			$oSaver->setObject($oAddress, 'tc_a_c');
		}
			
		// Rechnungs Addresse
//		$oSaver = new Ext_TS_Inquiry_Saver_Basic($this->_oRequest, $this->_oGui);
//		$oAddress = $this->_oObject->getAddress('billing');
//		$oSaver->setObject($oAddress, 'tc_a_b');

	}

	protected function prepareEmails() {

		self::prepareEmailsStatic($this->_oRequest, $this->_oObject);

	}

	/**
	 * Statische Methode, da in der Enquiry exakt dasselbe benötigt wird (wie fast immer)
	 *
	 * @param MVC_Request $oRequest
	 * @param Ext_TS_Contact $oContact
	 */
	public static function prepareEmailsStatic(MVC_Request $oRequest, Ext_TS_Contact $oContact) {

		$bFirst = true;
		$aEmails = (array)$oRequest->input('contact_email');

		foreach($aEmails as $iEmailAddressId => $sEmail) {

			if(empty($sEmail)) {
				continue;
			}

			/** @var Ext_TC_Email_Address $oEmailAddress */
			$oEmailAddress = $oContact->getJoinTableObject('contacts_to_emailaddresses', $iEmailAddressId);
			$oEmailAddress->email = $sEmail;

			// Erste E-Mail-Adresse ist automatisch Master-E-Mail
			if($bFirst) {
				$oEmailAddress->master = 1;
				$bFirst = false;
			}
		}

		$aDeleted = $oRequest->input('deleted');
		if(!empty($aDeleted['contact_email'])) {
			foreach(array_keys($aDeleted['contact_email']) as $iEmailAddressId) {
				$oEmailAddress = $oContact->getJoinTableObject('contacts_to_emailaddresses', $iEmailAddressId);
				$oEmailAddress->active = 0;
			}
		}

	}

	protected function prepareDetails() {

		$aSaveData = $this->getRequestSaveValues();
		foreach($aSaveData as $sColumn => $aAliases) {
			foreach($aAliases as $sAlias => $mValue) {
				if($sAlias == 'tc_c_d') {
					$this->_oObject->setDetail($sColumn, $mValue);
				}
			}
		}

	}

	/**
	 * @param mixed $mValue
	 * @param string $sColumn
	 * @return int|mixed
	 */
	public function prepareSaveValue($mValue, $sColumn) {

		$mValue = parent::prepareSaveValue($mValue, $sColumn);

		if($sColumn == 'birthday') {
			$oSchoolForFormat = Ext_Thebing_Client::getFirstSchool($this->_oGui->access);
			$mValue = Ext_Thebing_Format::ConvertDate($mValue, $oSchoolForFormat->id, 1, true);
		}

		return $mValue;

	}

}
