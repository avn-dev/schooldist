<?php

namespace TsHubspot\Service;

use TsHubspot\Service\Helper\General;

class AgencyContact extends Api {

	/** @var \Ext_Thebing_Agency_Contact */
	private $agencyContact;

	public function __construct($agencyContact) {
		parent::__construct();
		$this->agencyContact = $agencyContact;
	}


	public function update() {

		$existingProperties = General::getExistingProperties('contacts', $this->oHubspot);

		$lang = $this->agencyContact->getLanguage();

		$oLanguage = new \Tc\Service\Language\Frontend($lang);

		$gender = \Ext_TC_Util::getGenders(false, '', $oLanguage)[$this->agencyContact->gender];
		$salutation = \Ext_Thebing_Util::getPersonTitles($oLanguage)[$this->agencyContact->gender];

		$agency = \Ext_Thebing_Agency::getInstance($this->agencyContact->company_id);

		if (!empty($agency->ext_6)) {
			$country = \Data_Countries::getList($lang)[$agency->ext_6];
		}

		$helper = new General();
		$helper->setExistingProperties($existingProperties);

		// Gender als Select in Hubspot
		$helper->addProperty('gender', $this->agencyContact->gender);
		// Gender als Textfeld in Hubspot (Wenn es ein Select ist, wird der Wert bei "gender" nicht überschrieben, sonst schon)
		$helper->addProperty('gender', $gender);
		$helper->addProperty('salutation', $salutation);
		$helper->addProperty('firstname', $this->agencyContact->firstname);
		$helper->addProperty('lastname', $this->agencyContact->lastname);
		$helper->addProperty('email', $this->agencyContact->email);
		$helper->addProperty('phone', $this->agencyContact->phone);
		$helper->addProperty('fax', $this->agencyContact->fax);
		$helper->addProperty('hs_language', $lang);
		$helper->addProperty('country', $country);
		$helper->addProperty('company', $agency->getName(true));

		// Eigentlich bei allen Services kommt hier diese Methode hinzu, ich lasse das für den Agenturkontakt erstmal weg
		// weil es hier noch keinen extra Abschnitt in den externen App Einstellungen gibt, wo man Felder mappen kann.
		// Kann aber "leicht" hinzugefügt werden in der Zukunft -> muss dann aber eigentlich auch mit einem sync kommen wie
		// bei den Agenturen oder?
//		$helper->addAllGivenProperties($this->agencyContact);

		$properties = $helper->getProperties();

		$entityHubspotObject = new \HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput;

		$entityHubspotObject->setProperties($properties);

		$hubspotId = $helper->findHubspotIdByEntity($this->agencyContact);

		$newContact = false;
		try {
			$helper::increaseHubspotAPILimitCache();
			if (!empty($hubspotId)) {
				$this->oHubspot->crm()->contacts()->basicApi()->update($hubspotId, $entityHubspotObject);
			} else {
				$request = $this->oHubspot->crm()->contacts()->basicApi()->create($entityHubspotObject);
				$hubspotId = $request->getId();
				$helper->saveHubspotId($hubspotId, $this->agencyContact);
				$newContact = true;
			}
		} catch (\Throwable $exception) {
			if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
				$errorMessage = $exception->getResponseBody();
			} else {
				$errorMessage = $exception->getMessage();
			}
			$this->oLogger->error('Creating or Updating AgencyContact in Hubspot failed!', [$errorMessage]);

			throw $exception;
		}

		if ($newContact) {
			// Kontakt der Agentur zuweisen
			$agency = $this->agencyContact->getParentObject();
			$agencyHubspotId = $helper->findHubspotIdByEntity($agency);

			$success = false;
			if (empty($agencyHubspotId)) {
				// Agentur erstellen
				$agencyService = new Agency($agency);
				$success = $agencyService->update();
				$agencyHubspotId = $helper->findHubspotIdByEntity($agency);
			}

			if ($success) {
				try {
					$helper::increaseHubspotAPILimitCache();
					$this->oHubspot->apiRequest([
						'method' => 'put',
						'path' => '/crm-associations/v1/associations',
						'body' => [
							'fromObjectId' => $hubspotId,
							'toObjectId' => $agencyHubspotId,
							'definitionId' => 1,
							'category' => "HUBSPOT_DEFINED",
						]
					]);
				} catch (\Throwable $exception) {
					if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
						$errorMessage = $exception->getResponseBody();
					} else {
						$errorMessage = $exception->getMessage();
					}
					$this->oLogger->error('Creating Company Association failed!', [$errorMessage]);

					throw $exception;
				}
			}
		}

		return true;
	}

	public function getResponseMessage() {
		return sprintf(\L10N::t('Agenturkontakt "%s %s"'), $this->agencyContact->firstname, $this->agencyContact->lastname);
	}
}