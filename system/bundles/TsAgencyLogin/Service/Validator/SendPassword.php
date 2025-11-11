<?php

namespace TsAgencyLogin\Service\Validator;

use TcFrontend\Service\Validator\Validator;


/**
 * Class SendPassword
 *
 * Validierungsklasse des Prozess "Passwort senden"
 *
 * @package TsAgencyLogin\Service\Validator
 */
class SendPassword extends Validator {

	/**
	 * E-Mail Adresse der Agentur
	 *
	 * @var string
	 */
	private $sEmail = '';

	/**
	 * @inheritDoc
	 */
	protected function validate() {

		if(empty($this->sEmail)) {

			$this->appendErrorWithKey('name', 'Please enter an e-mail address!');

		} else {

			$this->oWdValidate->check = 'MAIL';
			$this->oWdValidate->value = $this->sEmail;

			if(!$this->oWdValidate->execute()) {

				$this->appendErrorWithKey('name', 'Please enter a valid e-mail address!');

			}
		}
	}

	/**
	 * @param string $sEmail
	 */
	public function setEmail($sEmail) {
		$this->sEmail = $sEmail;
	}
}