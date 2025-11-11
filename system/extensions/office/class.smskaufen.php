<?php

class Ext_Office_Smskaufen {
	
	protected $_sUsername = '';
	protected $_sPassword = '';
	protected $_aConfig = array();
	protected $_sUrl = '';
	protected $_iMode = 0;
	
	public function __construct($aConfig) {

		$this->_sUsername = $aConfig['smskaufen_username'];
		$this->_sPassword = $aConfig['smskaufen_password'];
		$this->_aConfig = $aConfig;

	}

	protected function _sendRequest($aParameter) {
	
		$ch = curl_init($this->_sUrl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $aParameter);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$sResponse = curl_exec($ch);
		curl_close($ch);

		return $sResponse;

	}
	
	public function setMode($iMode = 1) {
		$this->_iMode = $iMode;
	}
	
}