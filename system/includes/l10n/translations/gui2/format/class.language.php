<?php

class L10N_Translations_Gui2_Format_Language extends \Ext_Gui2_View_Format_Abstract {
	
	private static $aCache = [];

	private $sLanguageIso;
	
	public function __construct($sLanguageIso) {
		$this->sLanguageIso = $sLanguageIso;
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$aVerifications = $this->getVerifications($aResultData);
		
		if(
			empty($mValue) ||
			!isset($aVerifications[$this->sLanguageIso])
		) {
			return $mValue;
		}
		
		$sClass = 'fa fa-check-circle';
		if($aVerifications[$this->sLanguageIso] === "1") {
			$sClass .= ' system-color';
		}
				
		$sKey = implode('-', [$aResultData['id'], $this->sLanguageIso]);
		
		return '<i '
				. 'class="'.$sClass.'"'
				. 'data-key="'.$sKey.'"'
				. 'onClick="aGUI[\''.$this->oGui->hash.'\'].switchTranslationVerify('.$aResultData['id'].', \''.$this->sLanguageIso.'\');"'
				. 'style="font-size:16px;"'
				. '></i> '
				. $mValue;
	}
	
	private function getVerifications(array $aResultData) {
		
		if(!isset(self::$aCache[$aResultData['id']])) {
			if(isset($aResultData['verification'])) {
				$aLanguageData = explode('{||}', $aResultData['verification']);
				foreach($aLanguageData as $sLine) {
					$aVerificationData = explode('{|}', $sLine);
					self::$aCache[$aResultData['id']][$aVerificationData[0]] = $aVerificationData[1];
				}
			}
		}
		
		return self::$aCache[$aResultData['id']];
	}
	
}

