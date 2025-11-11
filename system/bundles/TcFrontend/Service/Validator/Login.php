<?php

namespace TcFrontend\Service\Validator;

/**
 * Class Login
 *
 * Validierungsklasse des Prozess "Agentur Anmeldung"
 *
 * @package TsAgencyLogin\Service
 */
class Login extends Validator {

	/**
	 * E-Mail-Adresse die eingegeben wurde
	 *
	 * @var string
	 */
	private $sEmail = '';

	/**
	 * Nutzername der eingegeben wurde
	 *
	 * @var string
	 */
	private $sName = '';

	/**
	 * Passwort das eingegeben wurde
	 *
	 * @var string
	 */
	private $sPassword = '';

	/**
	 * Sagt aus ob es die erste Validierung ist oder nicht
	 *
	 * @var bool
	 */
	private $bFirstValidate = true;

	/**
	 * Gehashtes Passwort der Agentur
	 *
	 * @var string
	 */
	private $sAgencyPassword = '';

	/**
	 * @inheritDoc
	 */
	protected function validate() {

		if (empty($this->sEmail)) {
			$this->appendError('Das Feld "E-Mail-Adresse" muss ausgefüllt werden!');
		}
		if (!empty($this->sEmail)) {
			$this->oWdValidate->value = $this->sEmail;
			$this->oWdValidate->check = 'MAIL';
			if (!$this->oWdValidate->execute()) {
				$this->appendError('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
			}
		}
		if (empty($this->sPassword)) {
			$this->appendError('Bitte geben Sie ein Passwort ein.');
		}

		if (!$this->bFirstValidate) {

		}

	}

	/**
	 * @param string $sEmail
	 */
	public function setEmail($sEmail) {
		$this->sEmail = $sEmail;
	}

	/**
	 * @param string $sName
	 */
	public function setName($sName) {
		$this->sName = $sName;
	}

	/**
	 * @param mixed $sPassword
	 */
	public function setPassword($sPassword) {
		$this->sPassword = $sPassword;
	}

	/**
	 * @param bool $bFirstValidate
	 */
	public function setFirstValidate($bFirstValidate) {
		$this->bFirstValidate = $bFirstValidate;
	}

	/**
	 * @param string $sAgencyPassword
	 */
	public function setAgencyPassword($sAgencyPassword) {
		$this->sAgencyPassword = $sAgencyPassword;
	}

}