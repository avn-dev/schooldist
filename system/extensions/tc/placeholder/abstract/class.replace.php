<?php

/**
 * @deprecated
 */
abstract class Ext_TC_Placeholder_Abstract_Replace {
	/**
	 * @var SmartyWrapper 
	 */
	protected $oSmarty;	
	/**
	 * @var array 
	 */
	protected $aPlaceholderParameters = array();
	/**
	 * @var string 
	 */
	protected $sDisplayLanguage = '';

	/**
	 * Konstruktor
	 * 
	 * @param SmartyWrapper $oSmarty
	 */
	final public function __construct(SmartyWrapper $oSmarty) {
		$this->oSmarty = $oSmarty;
	}
	
	/**
	 * Setzt die benötigte Sprache
	 * 
	 * @param string $sLanguageIso
	 */
	final public function setDisplayLanguage($sLanguageIso) {
		$this->sDisplayLanguage = $sLanguageIso;
	}
	
	/**
	 * Bindet in der Platzhalterklasse definierte Parameter an diese Klasse
	 * 
	 * @param array $aPlaceholderParameters
	 */
	final public function bindParameters(array $aPlaceholderParameters) {
		$this->aPlaceholderParameters = $aPlaceholderParameters;
	}
	
	/**
	 * Liefert einen Wert aus den definierten Parametern
	 * 
	 * @param string $sSetting
	 * @return mixed
	 */
	final protected function getParameterSetting($sSetting) {
		if($this->aPlaceholderParameters[$sSetting]) {
			return $this->aPlaceholderParameters[$sSetting];
		}
		
		return null;
	}

	/**
	 * Generiert den Inhalt für den Platzhalter, für den diese Klasse angegeben wurde
	 * 
	 * @param WDBasic $oEntity
	 * @param WDBasic $oParentEntity
	 */
	abstract public function replace(WDBasic $oEntity, WDBasic $oParentEntity = null);
}

