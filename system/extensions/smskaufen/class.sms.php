<?php

class Ext_Smskaufen_Sms extends Ext_Smskaufen {

	protected $_sMessage;
	protected $_iRecipient;

	public function __construct($aConfig) {

		parent::__construct($aConfig);
		
		$this->_sUrl = "https://www.smskaufen.com/sms/gateway/sms.php";

	}
	
	public function __set($sName, $mValue) {

		switch($sName) {
			case 'message':
				$mValue = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $mValue);
				$this->_sMessage = $mValue;
				break;
			case 'recipient':
				$this->_iRecipient = $mValue;
				break;
			default:
				break;
		}

	}

	protected function _prepareNumber($iNumber) {

		$iNumber = preg_replace("/[^0-9+]/i","", $iNumber);
		if(preg_match("/^\+/", $iNumber) == 1) {
			return "00".substr($iNumber, 1);
		} else {
			return $iNumber;
		}

	}

	public function post($sMessage, $sRecipient, $sSender=null) {

		$aParameter = array();

		if(
			$sSender === null &&
			isset($this->_aConfig['sender'])
		) {
			$sSender = $this->_aConfig['sender'];
		}

		$sMessage = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $sMessage);
		
		$iRecipient = $this->_prepareNumber($sRecipient);

		$aParameter['absender'] = $sSender;
		$aParameter['empfaenger'] = $iRecipient;
		$aParameter['text'] = $sMessage;
		$aParameter['type'] = 3;

		$mReturn = $this->_processRequest($aParameter);

		return $mReturn;

	}

	protected function _getErrorMessage($iCode) {

		$aCodes = array();
		$aCodes[100] = "Dispatch OK";
		$aCodes[101] = 'Dispatch OK';
		$aCodes[111] = "IP was blocked";
		$aCodes[112] = "Incorrect login data";
		$aCodes[120] = "Sender field is empty";
		$aCodes[121] = "Gateway field is empty";
		$aCodes[122] = "Text is empty";
		$aCodes[123] = "Recipient field is empty";
		$aCodes[129] = "Wrong sender";
		$aCodes[130] = "Gateway Error";
		$aCodes[131] = "Wrong number";
		$aCodes[132] = "Mobile phone is off";
		$aCodes[133] = "Query not possible";
		$aCodes[134] = "Number invalid";
		$aCodes[140] = "No credit";
		$aCodes[150] = "SMS blocked";
		$aCodes[170] = "Date wrong";
		$aCodes[171] = "Date too old";
		$aCodes[172] = "Too many numbers";
		$aCodes[173] = "Format wrong";
		
		return $aCodes[$iCode];
		
	}
	
}