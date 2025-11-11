<?php

namespace TcFrontend\Service\Validator;

/**
 * Class Validator
 *
 * Basis Validatorklasse
 *
 * @package TsAgencyLogin\Service
 */
abstract class Validator {

	/**
	 * @var \WDValidate
	 */
	protected $oWdValidate;

	/**
	 * Beinhaltet alle Fehlermeldungen
	 *
	 * @var array
	 */
	private $aErrors = [];

	/**
	 * Prüft ob die Validierung erfolgreich war
	 *
	 * @return bool
	 */
	public function isValid() {
		$this->validate();
		return empty($this->aErrors);
	}

	/**
	 * Validiert die Eingabe.
	 *
	 * @return void
	 */
	abstract protected function validate();

	/**
	 * Fügt eine neue Fehlermeldung hinzu
	 *
	 * @param string $sMessage
	 * @return void
	 */
	protected function appendError($sMessage) {
		$this->aErrors[] = \L10N::t($sMessage);
	}

	/**
	 * Fügt eine neue Fehlermeldung hinzu inklusive Key.
	 *
	 * @param string $sKey
	 * @param string $sMessage
	 *
	 * @return void
	 */
	protected function appendErrorWithKey($sKey, $sMessage) {
		$this->aErrors[$sKey] = \L10N::t($sMessage);
	}

	/**
	 * Gibt alle Fehlermeldungen zurück
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->aErrors;
	}

	/**
	 * @param \WDValidate $oWdValidate
	 * @return void
	 */
	public function setWDValidate(\WDValidate $oWdValidate) {
		$this->oWdValidate = $oWdValidate;
	}

}