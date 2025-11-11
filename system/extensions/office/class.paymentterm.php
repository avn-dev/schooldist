<?php

class Ext_Office_PaymentTerm extends WDBasic {
	
	protected $_sTable = 'office_payment_terms';
	
	public function getMessage($sLanguage='de') {

		if(empty($sLanguage)) {
			$sLanguage = 'de';
		}
		
		$sMessage = $this->_aData['message_'.$sLanguage];

		if(empty($sMessage)) {
			$sMessage = $this->_aData['message'];
		}

		return $sMessage;

	}
	
}