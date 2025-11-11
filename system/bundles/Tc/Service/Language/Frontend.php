<?php

namespace Tc\Service\Language;

class Frontend extends \Tc\Service\LanguageAbstract {

	protected $sContext = 'Global';

	public function __construct($sLanguage) {
		
		parent::__construct($sLanguage);

		$this->oL10N = new \Ext_TC_L10N($sLanguage, 'frontend');
		
	}
	
	public function translate($sTranslate) {

		if(empty($this->sLanguage)) {
			return $sTranslate;
		}

		return $this->oL10N->translate($sTranslate, $this->sContext);

	}
	
}
