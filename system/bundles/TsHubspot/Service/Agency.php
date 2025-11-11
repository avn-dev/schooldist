<?php

namespace TsHubspot\Service;

use SevenShores\Hubspot\Http\Response;
use TsHubspot\Service\Helper\General;

/**
 * @package TsHubspot\Resources\Service
 */
class Agency extends Api {


	/**
	 * Agentur Objekt
	 *
	 * @var \Ext_Thebing_Agency
	 */
	private $oAgency = null;

	/**
	 * Helper-Klasse für die Agentur Datenverarbeitung
	 *
	 * @var General|null
	 */
	protected $oHelper = null;

	public $properties;

	public function __construct($agency) {

		parent::__construct();

		$this->oAgency = $agency;

		$this->oHelper = new General();
	}

	public function update() {

		$existingProperties = General::getExistingProperties('companies', $this->oHubspot);

		if (!empty($this->oAgency->ext_6)) {
			$country = \Data_Countries::getList($this->oAgency->getLanguage())[$this->oAgency->ext_6];
		}

		$this->oHelper->setExistingProperties($existingProperties);

		$this->oHelper->addProperty('name', $this->oAgency->ext_1);
		$this->oHelper->addProperty('abbreviation', $this->oAgency->ext_2);
		$this->oHelper->addProperty('short', $this->oAgency->ext_2);
		$this->oHelper->addProperty('address', $this->oAgency->ext_3);
		$this->oHelper->addProperty('address2', $this->oAgency->ext_35);
		$this->oHelper->addProperty('hs_country_code', $this->oAgency->ext_4);
		$this->oHelper->addProperty('zip', $this->oAgency->ext_4);
		$this->oHelper->addProperty('city', $this->oAgency->ext_5);
		$this->oHelper->addProperty('state', $this->oAgency->state);
		$this->oHelper->addProperty('domain', $this->oAgency->ext_10);
		$this->oHelper->addProperty('website', $this->oAgency->ext_10);
		$this->oHelper->addProperty('country', $country);
		$this->oHelper->addProperty('founded_year', $this->oAgency->founding_year);

		$this->oHelper->addAllGivenProperties($this->oAgency);

		$properties = $this->oHelper->getProperties();

		// Hubspot akzeptiert nur Zahlenwerte für dieses Feld (Ist bei uns varchar und kein Zahlenfeld)
		// -> wird dementspreched nicht übernommen bei keinem Zahlenwert
		$numberOfEmployees = (int)$this->oAgency->staffs;

		if (!empty($numberOfEmployees)) {
			$properties['numberofemployees'] = $numberOfEmployees;
		}

		$entityHubspotObject = new \HubSpot\Client\Crm\Companies\Model\SimplePublicObjectInput();

		$entityHubspotObject->setProperties($properties);

		$hubspotId = $this->oHelper->findHubspotIdByEntity($this->oAgency);

		try {
			$this->oHelper::increaseHubspotAPILimitCache();
			if (!empty($hubspotId)) {
				$this->oHubspot->crm()->companies()->basicApi()->update($hubspotId, $entityHubspotObject);
			} else {
				$request = $this->oHubspot->crm()->companies()->basicApi()->create($entityHubspotObject);
				$hubspotId = $request->getId();
				$this->oHelper->saveHubspotId($hubspotId, $this->oAgency);
			}
		}  catch (\Throwable $exception) {
			if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
				$errorMessage = $exception->getResponseBody();
			} else {
				$errorMessage = $exception->getMessage();
			}
			$this->oLogger->error('Creating or updating Agency in Hubspot failed!', [$errorMessage]);

			throw $exception;
		}

		if ($this->oAgency->hubspot_id == $this->oHelper::SELECT_CREATEHUBSPOTCOMPANY_ID) {
			$this->oAgency->updateAttribute('hubspot_id', $hubspotId);
		}

		return true;
	}

	public function getResponseMessage() {
		return sprintf(\L10N::t('Agentur "%s"'), $this->oAgency->ext_1);
	}
}