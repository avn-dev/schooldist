<?php

namespace Ts\Proxy;

abstract class AbstractProxy extends \Tc\Proxy\Basic {
	
	protected $sLanguage;
	
	public function setLanguage($sLanguage) {
		$this->sLanguage = $sLanguage;
	}

	public function getName($sLanguageIso = null) {
		return $this->oEntity->getName($this->sLanguage);	
	}
	
}