<?php

class Ext_Smskaufen_Fax extends Ext_Smskaufen {

	public function __construct($aConfig) {

		parent::__construct($aConfig);
		
		$this->_sUrl = "https://www.smskaufen.com/sms/faxtmp/inbound.php";
	}
	
	public function post($sFilepath, $sCode, $sEmpfaenger) {
		global $system_data;
		
		$aParameter = array();

		$aParameter["empfaenger"] = $this->checkNumber($sEmpfaenger);

		$aParameter["abs_nr"] = $this->checkNumber($this->_aConfig['smskaufen_faxnumber']);
		$aParameter["abs_name"] = $this->_aConfig['smskaufen_faxname'];
		$aParameter["email"] = $this->_aConfig['smskaufen_email'];

		if (@filesize($sFilepath)){
			$aParameter["datei"] = base64_encode(file_get_contents($sFilepath));
		}

		$aParameter["code"] = $sCode;

		if(isset($this->_aConfig['feed'])) {
			$aParameter["feed"] = $system_data['domain'].$this->_aConfig['feed'];
		}

		$mReturn = $this->_processRequest($aParameter);

		return $mReturn;

	}
	
	protected function _getErrorMessage($iCode) {
		
		$aCodes = array();
		$aCodes['112'] = 'Falsche Userdaten / Wrong users dates';
		$aCodes['121'] = 'Empfänger fehlt / Recipient missing';
		$aCodes['122'] = 'Absender fehlt / Sender missing';
		$aCodes['123'] = 'Name fehler / Name missing';
		$aCodes['124'] = 'PDF fehlt / PDF file missing';
		$aCodes['125'] = 'Größer als 500 kb / Larger than 500 kb';
		$aCodes['140'] = 'Zu wenig Guthaben / No credit';
		$aCodes['160'] = 'Zugang gesperrt / Account closed';
		$aCodes['173'] = 'Empfänger-Nr. falsch / Number is wrong';

		return $aCodes[$iCode];
		
	} 

	/**
	 * get right phone number format
	 *
	 * @author	Mark Koopmann - mark.koopmann@doccheck.com
	 * @param	string
	 * @return	number
	 */
	public function checkNumber($sNumber, $sPrefix="00", $sCountry="49") {

		$sNumber = preg_replace("/[^0-9+]/i", "", $sNumber);
		if(substr($sNumber, 0, 4) == "00".$sCountry) {
			$sNumber = substr($sNumber, 2);
		} elseif(preg_match("/^\+/", $sNumber) == 1) {
			$sNumber = substr($sNumber, 1);
		} elseif(preg_match("/^0[1-9]/", $sNumber) == 1) {
			$sNumber = $sCountry.substr($sNumber, 1);
		}

		return $sPrefix.$sNumber;

	}

}