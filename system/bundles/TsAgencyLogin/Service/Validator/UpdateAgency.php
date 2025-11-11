<?php

namespace TsAgencyLogin\Service\Validator;

use TcFrontend\Service\Validator\Validator;

/**
 * Class UpdateAgency
 *
 * Validierungsklasse des Prozess "Agenturen bearbeiten".
 *
 * @package TsAgencyLogin\Service\Validator
 */
class UpdateAgency extends Validator {

	/**
	 * Name der Agentur.
	 *
	 * @var string
	 */
	private $sName = '';

	/**
	 * Kürzel der Agentur.
	 *
	 * @var string
	 */
	private $sShortName = '';

	/**
	 * E-Mail-Adresse der Agentur.
	 *
	 * @var string
	 */
	private $sEmail = '';

	/**
	 * Webseite der Agentur.
	 *
	 * @var string
	 */
	private $sWebsite = '';

	/**
	 * Adresse der Agentur.
	 *
	 * @var string
	 */
	private $sAddress = '';

	/**
	 * Postleitzahl der Agentur.
	 *
	 * @var string
	 */
	private $sZip = '';

	/**
	 * Stadt in der die Agentur ihren Sitz hat.
	 *
	 * @var string
	 */
	private $sCity = '';

	/**
	 * Land in dem die Agentur ihren Sitz hat.
	 *
	 * @var string
	 */
	private $sCountry = '';

	/**
	 * @inheritDoc
	 */
	protected function validate() {

		if(empty($this->sName)) {
			$this->appendErrorWithKey('name', 'Please enter a name!');
		}
		if(empty($this->sShortName)) {
			$this->appendErrorWithKey('short_name', 'Please enter an abbreviation!');
		}
		if(empty($this->sEmail)) {
			$this->appendErrorWithKey('email', 'Please enter an e-mail address!');
		} else {
			$this->oWdValidate->check = 'MAIL';
			$this->oWdValidate->value = $this->sEmail;
			if(!$this->oWdValidate->execute()) {
				$this->appendErrorWithKey('email', 'The e-mail address is not valid!');
			}
		}
		if(empty($this->sWebsite)) {
			$this->appendErrorWithKey('website', 'Please enter a website URL!');
		} else {
			$this->oWdValidate->check = 'URL';
			$this->oWdValidate->value = $this->sWebsite;
			if(!$this->oWdValidate->execute()) {
				$this->appendErrorWithKey('website', 'The entered website URL is not valid!');
			}
		}
		if(empty($this->sAddress)) {
			$this->appendErrorWithKey('address', 'Please enter an address!');
		}
		if(empty($this->sZip)) {
			$this->appendErrorWithKey('zip', 'Please enter a postcode!');
		} else {
			$this->oWdValidate->check = 'ZIP';
			$this->oWdValidate->value = $this->sZip;
			if(!$this->oWdValidate->execute()) {
				$this->appendErrorWithKey('zip', 'The postcode is not valid!');
			}
		}
		if(empty($this->sCity)) {
			$this->appendErrorWithKey('city', 'Please enter a city!');
		}
		if(empty($this->sCountry)) {
			$this->appendErrorWithKey('country', 'Please select a country!');
		}
	}

	/**
	 * @param string $sName
	 * @return void
	 */
	public function setName($sName) {
		$this->sName = $sName;
	}

	/**
	 * @param string $sShortName
	 * @return void
	 */
	public function setShortName($sShortName) {
		$this->sShortName = $sShortName;
	}

	/**
	 * @param string $sEmail
	 * @return void
	 */
	public function setEmail($sEmail) {
		$this->sEmail = $sEmail;
	}

	/**
	 * @param string $sWebsite
	 * @return void
	 */
	public function setWebsite($sWebsite) {
		$this->sWebsite = $sWebsite;
	}

	/**
	 * @param string $sAddress
	 * @return void
	 */
	public function setAddress($sAddress) {
		$this->sAddress = $sAddress;
	}

	/**
	 * @param string $sZip
	 * @return void
	 */
	public function setZip($sZip) {
		$this->sZip = $sZip;
	}

	/**
	 * @param string $sCity
	 * @return void
	 */
	public function setCity($sCity) {
		$this->sCity = $sCity;
	}

	/**
	 * @param string $sCountry
	 * @return void
	 */
	public function setCountry($sCountry) {
		$this->sCountry = $sCountry;
	}
}