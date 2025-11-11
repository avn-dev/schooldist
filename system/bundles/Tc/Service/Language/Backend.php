<?php

namespace Tc\Service\Language;

class Backend extends \Tc\Service\LanguageAbstract {
	
	public function __construct($sLanguage) {
		
		if(mb_strlen($sLanguage) !== 2) {
			throw new \RuntimeException('Language identifier must be two characters in length.');
		}
		
		parent::__construct($sLanguage);

		$this->oL10N = new \Ext_TC_L10N($sLanguage, 'backend');
		
	}

	/**
	 * @deprecated
	 * @see setContext()
	 * @param string $sPath
	 */
	public function setPath($sPath) {
		$this->setContext($sPath);
	}
	
	public function translate($sTranslate) {
		return $this->oL10N->translate($sTranslate, $this->sContext ?? 0);
	}
	
}
