<?php

class Ext_Thebing_Gui2_Format_Gender extends Ext_Gui2_View_Format_Abstract {

	protected $aGenders = array();

	public function __construct($bShort=false) {
		
		if($bShort) {
			// Übersetzung mit einem Zeichen funktioniert nicht so gut
			$aGenders = ['', 'm', 'f', 'd'];

			if(System::getInterfaceLanguage() === 'de') {
				// Nur Deutsch benötigt einen anderen Buchstaben
				$aGenders[2] = 'w';
			}
		} else {
			$aGenders = ['', L10N::t('männlich'), L10N::t('weiblich'), L10N::t('divers')];
		}
		
		$this->aGenders = $aGenders;

	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		return (string)$this->aGenders[$mValue];
	}

}