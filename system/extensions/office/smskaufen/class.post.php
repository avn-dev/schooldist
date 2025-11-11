<?php

class Ext_Office_Smskaufen_Post extends Ext_Office_Smskaufen {

	public function __construct($aConfig) {

		parent::__construct($aConfig);
		
		$this->_sUrl = "https://www.smskaufen.com/sms/post/postin.php";
		
	}
	
	public function post($sFilepath, $sCode, $bAusland=false) {
		global $system_data;
		
		$aParameter = array();
			
		$aParameter["id"] = $this->_sUsername;
		$aParameter["pw"] = $this->_sPassword;
		$aParameter["color"] = 'f';
		
		if (@filesize($sFilepath)){
			$aParameter["document"] = new CURLFile($sFilepath);
		}

		$aParameter["art"] = 'b';
		$aParameter["mode"] = $this->_iMode;
		$aParameter["code"] = $sCode;
		$aParameter["feed"] = $system_data['domain']."/system/extensions/office/office.api.php";
		$aParameter["email"] = $this->_aConfig['smskaufen_email'];
		
		if($bAusland) {
			$aParameter["ausland"] = 1;
		}

		$sResponse = $this->_sendRequest($aParameter);
		
		if(strlen($sResponse) == 3) {
			return $this->_getErrorMessage($sResponse);
		} else {
			return true;
		}
				
	}
	
	protected function _getErrorMessage($iCode) {
		
		$aCodes = array();
		$aCodes['112'] = 'Falsche Userdaten / Incorrect login data';
		$aCodes['120'] = 'Fehler Seitenanzahl / Wrong number of pages';
		$aCodes['122'] = 'Fehler bei Übergabe / Error on handover';
		$aCodes['123'] = 'Datei nicht vorhanden / File missing';
		$aCodes['124'] = 'Art fehlt / „art“ field empty';
		$aCodes['125'] = 'Absender fehlt / Sender field empty';
		$aCodes['126'] = 'Empfänger fehlt / Receiver field missing';
		$aCodes['127'] = 'Text fehlt / Text field missing';
		$aCodes['128'] = 'Text ist zu lang / Text too long';
		$aCodes['129'] = 'Allgemeiner Fehler / Gateway error';
		$aCodes['130'] = 'Bild über 2 MB / Picture larger than 2 mb';
		$aCodes['140'] = 'Zu wenig Guthaben / No credit';
		$aCodes['160'] = 'Zugang gesperrt / Account closed';
		
		return $aCodes[$iCode];
		
	} 
	
}