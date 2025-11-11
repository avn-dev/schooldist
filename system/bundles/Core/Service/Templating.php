<?php

namespace Core\Service;

class Templating extends \SmartyWrapper {
	
	/**
	 * @var \Tc\Service\LanguageAbstract
	 */
	protected $oLanguage = null;

	public function __construct() {

		parent::__construct();

	}

	public function setLanguage(\Tc\Service\LanguageAbstract $oLanguage) {
		$this->oLanguage = $oLanguage;
	}

	/**
	 * Übersetzungen im Template durch Übergabe von Sprachobjekt gezielt Front- oder Backend
	 * 
	 * @param string $sTranslation
	 * @param string $sPath
	 * @return string
	 */
	public function translate($sTranslation, $sPath = null) {
		
		if($this->oLanguage !== null) {
			if($sPath !== null) {
				$this->oLanguage->setContext($sPath);
			}
			return $this->oLanguage->translate($sTranslation);
		}
		
		return parent::translate($sTranslation, $sPath);
	}
	
}