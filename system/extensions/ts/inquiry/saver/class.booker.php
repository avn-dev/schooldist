<?php

class Ext_TS_Inquiry_Saver_Booker extends Ext_TS_Inquiry_Saver_Traveller {

	/**
	 * @var Ext_TS_Inquiry_Contact_Booker
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

	protected function prepareEmails()
	{
		$saveValues = $this->getRequestSaveValues();

		$sEmail = $saveValues['email']['tc_bc'];

		if(empty($sEmail)) {
			$this->_oObject->contacts_to_emailaddresses = [];
			return;
		}

		/** @var Ext_TC_Email_Address $oEmailAddress */
		$this->_oObject->email = $sEmail;
	}

	protected function prepareDetails() {

		$saveData = $this->getRequestSaveValues();

		$phone = $saveData['detail_phone_private']['tc_bc'];

		$this->_oObject->setDetail('phone_private', $phone);
	}

	protected function prepareAddresses() {

		// Addresse
//		$oSaver = new Ext_TS_Inquiry_Saver_Basic($this->_oRequest, $this->_oGui);
//		$oAddress = $this->_oObject->getAddress('contact');
//		$oSaver->setObject($oAddress, 'tc_a_c');

		// Rechnungs Addresse
		$oSaver = new Ext_TS_Inquiry_Saver_Basic($this->_oRequest, $this->_oGui);
		$oAddress = $this->_oObject->getAddress('billing');
		$oSaver->setObject($oAddress, 'tc_a_b');

	}

}
