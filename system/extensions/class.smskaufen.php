<?php

class Ext_Smskaufen {
	
	protected $_sUsername = '';
	protected $_sPassword = '';
	protected $_sApiKey = '';
	protected $_aConfig = array();
	protected $_sUrl = '';
	protected $_iMode = 0;

	public function __construct($aConfig) {

		$this->_sUsername = $aConfig['smskaufen_username'];
		$this->_sPassword = $aConfig['smskaufen_password'];
		$this->_sApiKey = $aConfig['smskaufen_apikey'];
		$this->_aConfig = $aConfig;

	}

	protected function _processRequest($aParameter) {
		
		$sResponse = $this->_sendRequest($aParameter);
		
		if(strlen($sResponse) == 3) {
			return $this->_getErrorMessage($sResponse);
		} else {
			return true;
		}

	}
	
	protected function _sendRequest($aParameter) {
	
		$aParameter["id"] = $this->_sUsername;
		
		if(!empty($this->_sApiKey)) {
			$aParameter["apikey"] = $this->_sApiKey;
		} else {
			$aParameter["pw"] = $this->_sPassword;
		}
		
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