<?php

namespace Tc\Service;

abstract class LanguageAbstract {

	/**
	 * @var string
	 */
	protected $sLanguage;

	/**
	 * @var string
	 */
	protected $sContext;
	
	/**
	 * @var \L10N
	 */
	protected $oL10N;

	/**
	 * @param string $sLanguage
	 */
	public function __construct($sLanguage) {

		if(!is_string($sLanguage)) {
			throw new \InvalidArgumentException('Language is not a string!');
		}

		$this->sLanguage = $sLanguage;

	}

	/**
	 * @param string $sTranslate Übersetzung
	 * @return string
	 */
	abstract public function translate($sTranslate);

	/**
	 * @return string
	 */
	public function getLanguage() {
		return $this->sLanguage;
	}

	/**
	 * Kontext, Description, Pfad, File, File-Id, mUse, […]
	 *
	 * @param string $sContext
	 */
	public function setContext($sContext) {
		$this->sContext = $sContext;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getContext(): string {
		return $this->sContext;
	}

}
