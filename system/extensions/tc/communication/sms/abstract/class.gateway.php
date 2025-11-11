<?php

/**
 * Basisklasse für SMS-Gatewaay
 */
abstract class Ext_TC_Communication_SMS_Abstract_Gateway {

	protected $_sRecipient = '';
	protected $_sMessage = '';
	protected $_sSender = null;
	
	
	public function setRecipient($sRecipient)
	{
		$this->_sRecipient = $sRecipient;
	}
	
	public function setMessage($sMessage)
	{
		$this->_sMessage = $sMessage;
	}
	
	public function getMessage() {
		return $this->_sMessage;
	}
	
	public function setSender($sSender) {
		$this->_sSender = $sSender;
	}
	
	public function getSender() {
		return $this->_sSender;
	}
	
	abstract public function send();
	
}