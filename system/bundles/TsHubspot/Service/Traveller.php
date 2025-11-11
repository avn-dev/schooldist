<?php

namespace TsHubspot\Service;

use TsHubspot\Service\Helper\General;

class Traveller extends Api {

	/**
	 * @var \Ext_TS_Inquiry_Contact_Traveller
	 */
	private $traveller;

	private $oHelper;

	private $hubspotId;

	private $inquiry;

	private $email;

	public function __construct($traveller, $hubspotId, $inquiry) {

		parent::__construct();

		$this->traveller = $traveller;

		$this->hubspotId = $hubspotId;

		$this->inquiry = $inquiry;

		$this->oHelper = new General();
	}


	public function update() {

		$agency = $this->inquiry->getAgency();
		$birthday = General::formatDate($this->traveller->birthday, $this->traveller->getSchool()->date_format_long);

		if (!empty($agency)) {
			$agencyName = $agency->getName();
		} else {
			$agencyName = '';
		}

		$lang = $this->traveller->getSchool()->getLanguage();

		$gender = $this->traveller->getFrontendGender($lang);

		$address = $this->traveller->getAddress('contact', false);

		if (!empty($address)) {
			$country = $address->getCountry($lang);
		} else {
			$country = '';
		}

		$this->oHelper->setExistingPropertiesAndValidationRules('contacts', $this->oHubspot);

		$alreadyExistingContactAction = \System::d('hubspot_already_existing_contact_action');

		// Bei keiner Hubspot-Kontaktsuche
		if (empty($this->hubspotId)) {
			if ($alreadyExistingContactAction == 'new_deal') {
				// Gleicher Kontakt
				$this->hubspotId = $this->oHelper->findHubspotIdByEntity($this->traveller);
			} else {
				// Bei "new_contact" muss die Traveller-Hubspot-Id zur Buchung gespeichert werden, da sonst eine
				// Verbindung zum ggf. bereits existierenden Traveller gemacht wird und dann kein neuer Kontakt erstellt wird.
				$this->hubspotId = $this->oHelper->findTravellerHubspotIdByInquiry($this->inquiry);
			}
		} else {
			// Wenn der Kontakt bereits existiert in Hubspot, in Fidelo aber der Traveller ein anderer ist und man das
			// nicht möchte -> (Bei Hubspot-Kontaktsuche oder wenn "Weiteren Deal ergänzen" in den Externen-App-Einstellungen
			// ausgewählt wurde)
			$oldTravellerHubspotId = $this->oHelper->findHubspotIdByEntity($this->traveller);
			if (
				!empty($oldTravellerHubspotId) &&
				$oldTravellerHubspotId != $this->hubspotId
			) {
				// Wenn es schon einen Hubspot-Traveller zu der Buchung gab und es nicht der neue Hubspot-Traveller ist
				$this->oHelper->deleteHubspotId($oldTravellerHubspotId, $this->traveller);
			}
			$this->oHelper->saveHubspotId($this->hubspotId, $this->traveller);
		}

		// Der ganze Block: Wenn die E-Mail bereits existiert
		$this->email = $this->traveller->email;
		if (!empty($this->email)) {

			// Siehe Methodenbeschreibung
			$hubspotIdOfContactWithThatEmail = $this->contactExistsInHubSpot();
			if ($hubspotIdOfContactWithThatEmail) {
				// Wenn es einen Kontakt mit der E-Mail schon gibt
				switch($alreadyExistingContactAction) {
					case 'new_contact':
						// Etwas dran hängen, oder nichts machen, falls es nur ein Update und kein Create ist
						// -> (Kontakt existiert schon)
						$this->addEmailIncrement();
						break;
					case 'new_deal':
						// Dem Traveller hier die HubspotId von dem Kontakt mit der E-Mail in Hubspot geben, damit später
						// die Verbindung damit hergestellt werden kann.
						$oldHubspotId = $this->hubspotId;
						if (
							!empty($oldHubspotId) &&
							$oldTravellerHubspotId != $this->hubspotId
						) {
							// Wenn es schon einen Hubspot-Traveller zu der Buchung gab und es nicht der neue Hubspot-Traveller ist
							$this->oHelper->deleteHubspotId($oldTravellerHubspotId, $this->traveller);
						}
					// Bei "send_error" wird einfach nichts geändert -> unten wird ein Fehler geschmissen, abgefangen, etwas
					// formatiert und zurückgeschickt an den Benutzer.
				}
			}
		}

		// Wenn email in den externen Einstellungen als Feld ausgewählt wurde, kann der Wert nicht vom getter kommen.
		$this->oHelper->travellerEmail = $this->email;

		$this->oHelper->addProperty('firstname', $this->traveller->firstname);
		$this->oHelper->addProperty('lastname', $this->traveller->lastname);
		$this->oHelper->addProperty('date_of_birth', $birthday);
		$this->oHelper->addProperty('birthday', $birthday);
		// Gender als Select in Hubspot
		$this->oHelper->addProperty('gender', $this->traveller->gender);
		// Gender als Textfeld in Hubspot (Wenn es ein Select ist, wird der Wert bei "gender" nicht überschrieben, sonst schon)
		$this->oHelper->addProperty('gender', $gender);
		$this->oHelper->addProperty('address', $address->address);
		$this->oHelper->addProperty('zip', $address->zip);
		$this->oHelper->addProperty('city', $address->city);
		$this->oHelper->addProperty('state', $address->state);
		$this->oHelper->addProperty('country', $country);
		$this->oHelper->addProperty('phone', $this->traveller->getFirstPhoneNumber());
		$this->oHelper->addProperty('mobilephone', $this->traveller->getFirstMobilePhoneNumber());
		$this->oHelper->addProperty('email', $this->email);
		$this->oHelper->addProperty('fax', $this->traveller->getFirstFaxNumber());
		$this->oHelper->addProperty('company', $agencyName);

		// Weitere Eigenschaften aus den externen App Einstellungen hinzufügen.
		// Beim Traveller auch Inquiry weil die (erstmal) gleich behandelt werden
		$this->oHelper->addAllGivenProperties($this->inquiry);

		$properties = $this->oHelper->getProperties();

		$entityHubspotObject = new \HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput;

		$entityHubspotObject->setProperties($properties);

		try {
			$this->oHelper::increaseHubspotAPILimitCache();
			if (!empty($this->hubspotId)) {
				$this->oHubspot->crm()->contacts()->basicApi()->update($this->hubspotId, $entityHubspotObject);
			} else {
				$request = $this->oHubspot->crm()->contacts()->basicApi()->create($entityHubspotObject);
				$this->hubspotId = $request->getId();
				$this->oHelper->saveHubspotId($this->hubspotId, $this->traveller);
			}
		} catch (\Throwable $exception) {
			if (
				$exception instanceof \HubSpot\Client\Crm\Contacts\ApiException ||
				$exception instanceof \HubSpot\Client\Crm\Objects\ApiException
			) {
				$errorMessage = $exception->getResponseBody();
			} else {
				$errorMessage = $exception->getMessage();
			}
			$this->oLogger->error('Creating or updating TravellerContact in Hubspot failed!', [$errorMessage]);
			throw $exception;
		}

		return $this->hubspotId;
	}

	public function getAllContactsByEmail() {
		$this->oHelper::increaseHubspotAPILimitCache();
		return json_decode($this->oHubspot->apiRequest([
			'method' => 'get',
			'path' => '/crm/v3/objects/contacts/'.$this->email.'?idProperty=email',
		])->getBody()->getContents(), true);
	}

	/**
	 * Etwas dran hängen, oder nichts machen, falls es nur ein Update und kein Create ist
	 */
	public function addEmailIncrement() {

		$emailParts = explode('@', $this->email, 2);
		$compareableEmail = $emailParts[0];
		$hubspotAdditionalMultipleEmails = \System::d('hubspot_additional_multiple_emails');
		$sameEmailAmount = 0;
		if (!empty($hubspotAdditionalMultipleEmails)) {
			$compareableEmail .= '+'.$hubspotAdditionalMultipleEmails;
		}

		try {
			do {
				// Wenn man hier landet gibt es die E-Mail schon, weil sonst eine Exception geschmissen worden wäre
				// in der getAllContactsByEmail()
				$tempEmail = $compareableEmail;
				$sameEmailAmount++;

				$this->email = $tempEmail.'+'.$sameEmailAmount.'@'.$emailParts[1];
			} while (
				// Wenn der Contact nicht man selber ist und bei 25 Durchführungen stoppen sicherheitshalbe
				$this->getAllContactsByEmail()['id'] != $this->hubspotId &&
				$sameEmailAmount<25
			);
		} catch (\Throwable $exception) {
			if (
				$exception instanceof \GuzzleHttp\Exception\ClientException &&
				$exception->getResponse()->getStatusCode() === 404
			) {
				// Bei dem Fehler gibt es die E-Mail noch nicht, also wird die E-Mail einfach hinzugefügt.
			} else {
				// Anderer (unerwarteter) Fehler
				if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
					$errorMessage = $exception->getResponseBody();
				} else {
					$errorMessage = $exception->getMessage();
				}
				$this->oLogger->error('Getting Contact by E-Mail in Hubspot failed!', [$errorMessage]);

				throw $exception;
			}
		}
	}

	/**
	 * 	Returnt true, wenn der Kontakt schon existiert UND der HubSpot Kontakt mit der E-Mail ist nicht der Traveller selber.
	 * (Ich wollte die Methoden irgend wie passend dazu benennen, aber der Methodenname wäre dann sehr lang geworden.)
	 * -> Wenn es die E-Mail in HubSpot noch nicht gibt oder die E-Mail existiert, aber der Traveller selber dieser Kontakt ist,
	 * (also bei keinem Fehler) wird false returned.
	 */
	public function contactExistsInHubSpot() {
		try {
			$hubspotIdOfContactWithThatEmail = $this->getAllContactsByEmail()['id'];
			if ($hubspotIdOfContactWithThatEmail != $this->hubspotId) {
				return $hubspotIdOfContactWithThatEmail;
			} else {
				return false;
			}
		} catch (\Throwable $exception) {
			if (
				$exception instanceof \GuzzleHttp\Exception\ClientException &&
				$exception->getResponse()->getStatusCode() === 404
			) {
				// Bei dem Fehler gibt es die E-Mail noch nicht.
				return false;
			} else {
				// Anderer (unerwarteter) Fehler
				if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
					$errorMessage = $exception->getResponseBody();
				} else {
					$errorMessage = $exception->getMessage();
				}
				$this->oLogger->error('Getting Contact by E-Mail in Hubspot failed!', [$errorMessage]);

				throw $exception;
			}
		}
	}

}