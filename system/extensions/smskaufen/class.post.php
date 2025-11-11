<?php

class Ext_Smskaufen_Post extends Ext_Smskaufen {

	public function __construct($aConfig) {

		parent::__construct($aConfig);
		
		$this->_sUrl = "https://www.smskaufen.com/sms/post/postin.php";
		
	}
	
	public function post($sFilepath, $sCode, $bAusland=false) {
		global $system_data;

		$aParameter = array();

		$aParameter["color"] = 'f';
		
		if (@filesize($sFilepath)){
			$aParameter["document"] = "@".$sFilepath;
		}
	
		$aParameter["art"] = 'b';
		$aParameter["mode"] = $this->_iMode;
		$aParameter["code"] = $sCode;
		
		if(isset($this->_aConfig['feed'])) {
			$aParameter["feed"] = $system_data['domain'].$this->_aConfig['feed'];
		}
		
		$aParameter["email"] = $this->_aConfig['smskaufen_email'];
		
		if($bAusland) {
			$aParameter["ausland"] = 1;
		}

		$mReturn = $this->_processRequest($aParameter);

		return $mReturn;

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