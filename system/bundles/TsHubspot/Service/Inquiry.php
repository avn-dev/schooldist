<?php

namespace TsHubspot\Service;

use Core\Enums\AlertLevel;
use TsHubspot\Service\Helper\General;
use TsHubspot\Service\Helper\Inquiry as InquiryHelper;
use SevenShores\Hubspot\Http\Response;
use HubSpot\Client\Crm\Associations\V4\Model\AssociationSpec;

/**
 * @package TsHubspot\Resources\Service
 */
class Inquiry extends Api {

	const DEAL_OBJECT_TYPE_ID = '0-3';

	const DEAL_TO_CONTACT_ASSOCIATION_DEFINITION_ID = 3;
	const DEAL_TO_COMPANY_ASSOCIATION_DEFINITION_ID = 5;
	/**
	 * Buchung Objekt
	 *
	 * @var \Ext_TS_Inquiry
	 */
	private $oInquiry = null;

	/**
	 * Helper-Klasse für die Datenverarbeitung der Buchungen
	 *
	 * @var InquiryHelper|null
	 */
	protected $oHelper = null;


	/**
	 * @var \Ext_TS_Inquiry|null
	 */
	protected $inquiry;

	protected $hubspotId;

	public static $travellerHubspotId;

	private $agencyContactHubspotId;

	public function __construct($inquiry) {

		$this->oHelper = new General();

		parent::__construct();

		$this->inquiry = $inquiry;

	}

	/**
	 * {@inheritdoc}
	 */
	public function transfer() {}

	/**
	 * {@inheritdoc}
	 */
	public function incomingApiCall(\MVC_Request $oRequest) {}

	public function update() {

		$this->oHelper->setExistingPropertiesAndValidationRules('deals', $this->oHubspot);
		// Die beiden Properties werden überschrieben, falls angegeben in den externen App Einstellungen
		$this->oHelper->addProperty('dealname', $this->inquiry->name);
		$this->oHelper->addProperty('dealstage', 'qualifiedtobuy');
//		$this->oHelper->addProperty('dealtype', ''); #?

		// Weitere Eigenschaften aus den externen App Einstellungen hinzufügen.
		$this->oHelper->addAllGivenProperties($this->inquiry);

		$properties = $this->oHelper->getProperties();

		$entityHubspotObject = new \HubSpot\Client\Crm\Deals\Model\SimplePublicObjectInput;

		$entityHubspotObject->setProperties($properties);

		$traveller = $this->inquiry->getTraveller();

		// Traveller erstellen / updaten
		$travellerService = new Traveller($traveller, self::$travellerHubspotId, $this->inquiry);

		self::$travellerHubspotId = $travellerService->update();

		$this->hubspotId = $this->oHelper->findHubspotIdByEntity($this->inquiry);

		// Deal erstellen / updaten
		try {
			$this->oHelper::increaseHubspotAPILimitCache();
			if (!empty($this->hubspotId)) {
				$this->oHubspot->crm()->deals()->basicApi()->update($this->hubspotId, $entityHubspotObject);
			} else {
				$request = $this->oHubspot->crm()->deals()->basicApi()->create($entityHubspotObject);
				$this->hubspotId = $request->getId();
				// Bei der Buchung mit Traveller-Hubspot-Id für "new_contact" vor allem.
				$this->oHelper->saveHubspotId($this->hubspotId, $this->inquiry, self::$travellerHubspotId);
			}
		} catch (\Throwable $exception) {
			if (
				$exception instanceof \HubSpot\Client\Crm\Deals\ApiException ||
				$exception instanceof \HubSpot\Client\Crm\Objects\ApiException
			) {
				$errorMessage = $exception->getResponseBody();
			} else {
				$errorMessage = $exception->getMessage();
			}
			$this->oLogger->error('Creating or updating Deal in Hubspot failed!', [$errorMessage]);

			throw $exception;
		}

		// Traveller der Buchung zuweisen
		// Success auf true, weil sonst eine Exception geschmissen worden wäre
		$this->createAssociation(true, self::$travellerHubspotId, 'Traveller');

		// Leistungen erstellen / updaten und Zuweisungen der Leistungen erstellen:
		$this->updateService(new InquiryCourses(), $this->inquiry->getCourses());
		$this->updateService(new InquiryAccommodations(), $this->inquiry->getAccommodations());
		$this->updateService(new InquiryTransfers(), $this->inquiry->getTransfers());
		$this->updateService(new InquiryInsurances(), $this->inquiry->getInsurances());
		$this->updateService(new InquiryPayments(), $this->inquiry->getPayments());

		$agency = $this->inquiry->getAgency();
		$agencyContact = $this->inquiry->getAgencyContact();

		if ($agencyContact->id > 0) {
			$this->agencyContactHubspotId = $this->oHelper->findHubspotIdByEntity($agencyContact);

			$success = false;
			if (empty($this->agencyContactHubspotId)) {
				// Agenturkontakt (+ Agentur) erstellen
				$agencyContactService = new AgencyContact($agencyContact);
				$success = $agencyContactService->update();
				$this->agencyContactHubspotId = $this->oHelper->findHubspotIdByEntity($agencyContact);
			}

			$this->createAssociation($success, $this->agencyContactHubspotId, 'AgencyContact');

		}

		if (!empty($agency)) {

			$agencyHubspotId = $this->oHelper->findHubspotIdByEntity($agency);

			$success = false;
			if (empty($agencyHubspotId)) {
				// Agentur erstellen
				$agencyService = new Agency($agency);
				$success = $agencyService->update();
				$agencyHubspotId = $this->oHelper->findHubspotIdByEntity($agency);
			}

			$this->createAssociation($success, $agencyHubspotId, 'Agency');
		}

		return true;
	}

	public function getResponseMessage() {
		return sprintf(\L10N::t('Buchung "%s"'), $this->inquiry->getTraveller()->getCustomerNumber());
	}

	public function updateService($serviceUpdateServiceClass, $services) {
		$hubspotObjectId = \System::d($serviceUpdateServiceClass::HUBSPOT_OBJECT_KEY, '');
		if ($hubspotObjectId === 'deals') {
			// Wenn der Deal als Objekt ausgewählt ist für die Leistungen, dann dem Deal Property Array diese Werte hinzufügen
			// und natürlich dann kein anderes Objekt nochmal erstellen. Das Deal Properties hinzufügen passiert in der
			// addAllGivenProperties(), wenn "deals" eben ausgewählt wurde.
			return;
		} else {
			if (
				!empty($services) &&
				// Wenn das Service Objekt angegeben wurde in den Hubspot Settings
				!empty($hubspotObjectId)
			) {
				$hubspotServiceObject = $this->validateServiceSettings($serviceUpdateServiceClass, $hubspotObjectId);

				if (!empty($hubspotServiceObject)) {

					foreach ($hubspotServiceObject->getAssociations() as $association) {
						// Wenn die Assoziation vom Deal zum Service geht
						if ($association->getFromObjectTypeId() == self::DEAL_OBJECT_TYPE_ID) {
							$associationId = $association->getId();
						}
					}

					foreach ($services as $service) {
						// Service erstellen / updaten
						$newService = $serviceUpdateServiceClass->update($service);

						if ($newService) {
							$this->createServiceAssociation($associationId, $service, $serviceUpdateServiceClass);
						}
					}
				} else {
					// Wegen des PPs
					$user = \TsHubspot\Handler\ParallelProcessing\Transfer::$user;
					\Core\Service\NotificationService::sendToUser($user, sprintf(\L10N::t('"%s" konnte nicht aktualisiert werden: Nicht alle benötigten Eigenschaften wurden in den Hubspot Einstellungen angegeben.'), $serviceUpdateServiceClass::SERVICE_FOR_ERROR), AlertLevel::DANGER);
				}
			}
		}
	}

	public function validateServiceSettings($serviceUpdateServiceClass, $hubspotObjectId) {

		// Wenn mindestens alle required Eigenschaften von dem Objekt angegeben wurden
		// Nur für custom Objekte, weil es bei Standardobjekten keine Pflichtfelder gibt bzw. nur die, von Hubspot selber.
		// -> Die kennt man und kann diese explizit setzen oder nach denen Abfragen und braucht diese Methode nicht.
		try {
			General::increaseHubspotAPILimitCache();
			$hubspotServiceObject = $this->oHubspot->crm()->schemas()->CoreApi()
				->getById(General::HUBSPOT_CUSTOM_OBJECT_IDENTIFIER.$hubspotObjectId);
		} catch (\Throwable $exception) {
			if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
				$errorMessage = $exception->getResponseBody();
			} else {
				$errorMessage = $exception->getMessage();
			}
			$this->oLogger->error('Getting HubspotServiceObject failed!', [$errorMessage]);

			throw $exception;
		}
		$requiredProperties = $hubspotServiceObject->getRequiredProperties();
		$conf = new \Ext_TS_Config();
		$hubspotFieldPropertiesKey = $serviceUpdateServiceClass::HUBSPOT_FIELD_PROPERTIES_KEY;
		$hubspotFieldProperties = $conf->$hubspotFieldPropertiesKey;
		if ($serviceUpdateServiceClass::SERVICE_STRING === 'course') {
			$hubspotCustomFieldPropertiesKey = $serviceUpdateServiceClass::HUBSPOT_CUSTOMFIELD_PROPERTIES_KEY;
			$hubspotCustomFieldProperties = $conf->$hubspotCustomFieldPropertiesKey;
		}
		if (
			$serviceUpdateServiceClass::SERVICE_STRING === 'accommodation' ||
			$serviceUpdateServiceClass::SERVICE_STRING === 'course'
		) {
			$hubspotAdditionalServicesPropertiesKey = $serviceUpdateServiceClass::HUBSPOT_ADDITIONAL_SERVICES_PROPERTIES_KEY;
			$hubspotAdditionalServicesProperties = $conf->$hubspotAdditionalServicesPropertiesKey;
		}

		$success = 0;
		foreach ($requiredProperties as $requiredPropertyKey) {
			// Normale Felder durchgehen
			foreach ($hubspotFieldProperties as $hubspotFieldProperty) {
				if (
					!empty($hubspotFieldProperty['fidelo_field']) &&
					(
						$hubspotFieldProperty['hubspot_property_name_1'] === $requiredPropertyKey ||
						$hubspotFieldProperty['hubspot_property_name_2'] === $requiredPropertyKey ||
						$hubspotFieldProperty['hubspot_property_name_3'] === $requiredPropertyKey
					)
				) {
					$success += 1;
					continue 2;
				}
			}

			// Custom Felder durchgehen
			foreach ($hubspotCustomFieldProperties as $hubspotCustomFieldProperty) {
				if (
					!empty($hubspotCustomFieldProperty['fidelo_custom_field_id']) &&
					(
						$hubspotCustomFieldProperty['hubspot_property_name_1'] === $requiredPropertyKey ||
						$hubspotCustomFieldProperty['hubspot_property_name_2'] === $requiredPropertyKey ||
						$hubspotCustomFieldProperty['hubspot_property_name_3'] === $requiredPropertyKey
					)
				) {
					$success += 1;
					continue 2;
				}
			}

			// Zusatzleistungen durchgehen
			foreach ($hubspotAdditionalServicesProperties as $hubspotAdditionalServicesProperty) {
				if (
					!empty($hubspotAdditionalServicesProperty['fidelo_additional_service_id']) &&
					(
						$hubspotAdditionalServicesProperty['hubspot_property_name_1'] === $requiredPropertyKey ||
						$hubspotAdditionalServicesProperty['hubspot_property_name_2'] === $requiredPropertyKey ||
						$hubspotAdditionalServicesProperty['hubspot_property_name_3'] === $requiredPropertyKey
					)
				) {
					$success += 1;
					continue 2;
				}
			}
		}

		if ($success >= count($requiredProperties)) {
			return $hubspotServiceObject;
		}


		return null;
	}

	public function createServiceAssociation($associationId, $service, $serviceHelper) {

		if (!empty($associationId)) {
			// Service der Buchung zuweisen
			try {
				$this->oHelper::increaseHubspotAPILimitCache();
				$serviceHubspotId = $this->oHelper->findHubspotIdByEntity($service);
				$this->oHubspot->apiRequest([
					'method' => 'put',
					'path' => '/crm/v3/objects/deals/' . $this->hubspotId . '/associations/'.General::HUBSPOT_CUSTOM_OBJECT_IDENTIFIER . \System::d($serviceHelper::HUBSPOT_OBJECT_KEY) . '/' . $serviceHubspotId . '/'.$associationId,
				]);
			} catch (\Throwable $exception) {
				if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
					$errorMessage = $exception->getResponseBody();
				} else {
					$errorMessage = $exception->getMessage();
				}
				$this->oLogger->error('Creating Deal and '.get_class($serviceHelper).' Association failed!', [$errorMessage]);

				throw $exception;
			}
		}
	}

	public function createAssociation($success, $hubspotObjectId, $objectType) {

		if ($objectType == 'Agency') {
			$definitionId = self::DEAL_TO_COMPANY_ASSOCIATION_DEFINITION_ID;
		} else {
			// Bei Agenturmitarbeiter oder Traveller
			$definitionId = self::DEAL_TO_CONTACT_ASSOCIATION_DEFINITION_ID;
		}

		$hubspotObjectIdFromAssociation = $this->getHubspotIdFromAssociation($definitionId, $objectType);

		if (
			// Wenn das Objekt in Hubspot existiert
			( $success ||
				!empty($hubspotObjectId) ) &&
			// Wenn es die Zuweisung nicht schon gibt
			($hubspotObjectIdFromAssociation == false ||
				$hubspotObjectIdFromAssociation != $hubspotObjectId)
		) {

			$associationSpec2 = new AssociationSpec([
				'association_category' => 'HUBSPOT_DEFINED',
				'association_type_id' => $definitionId
			]);

			if ($objectType == 'Agency') {
				$hubspotObjectType = 'companies';
			} else {
				// Bei Agenturmitarbeiter oder Traveller
				$hubspotObjectType = 'contacts';
			}

			try {
				General::increaseHubspotAPILimitCache();
				$this->oHubspot->crm()->associations()->v4()->basicApi()->create('deals', $this->hubspotId, $hubspotObjectType, (string)$hubspotObjectId, [$associationSpec2]);
			} catch (\Throwable $exception) {
				if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
					$errorMessage = $exception->getResponseBody();
				} else {
					$errorMessage = $exception->getMessage();
				}
				$this->oLogger->error('Creating Deal and '.$objectType.' Association failed!', [$errorMessage]);

				throw $exception;
			}

			// Wenn es schon eine Zuweisung gibt
			if ($hubspotObjectIdFromAssociation != false) {
				// Alte Zuweisung löschen
				try {
					General::increaseHubspotAPILimitCache();
					$this->oHubspot->crm()->associations()->v4()->basicApi()->archive('deals', $this->hubspotId, $hubspotObjectType, (string)$hubspotObjectIdFromAssociation);
				} catch (\Throwable $exception) {
					if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
						$errorMessage = $exception->getResponseBody();
					} else {
						$errorMessage = $exception->getMessage();
					}
					$this->oLogger->error('Deleting Deal and .'.$objectType.' Association failed!', [$errorMessage]);

					throw $exception;
				}
			}
		}
	}

	public function getHubspotIdFromAssociation($definitionId, $contactType = ''): int | false {

		try {
			General::increaseHubspotAPILimitCache();
			$associationHubspotIds = json_decode($this->oHubspot->apiRequest([
				'method' => 'get',
				'path' => '/crm-associations/v1/associations/' . $this->hubspotId . '/HUBSPOT_DEFINED/' . $definitionId,
			])->getBody()->getContents())->results;
		} catch (\Throwable $exception) {
			if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
				$errorMessage = $exception->getResponseBody();
			} else {
				$errorMessage = $exception->getMessage();
			}
			$this->oLogger->error('Getting AssociationHubspotIds failed!', [$errorMessage]);

			throw $exception;
		}

		// Beim Traveller / Agenturmitarbeiter
		if ($definitionId == self::DEAL_TO_CONTACT_ASSOCIATION_DEFINITION_ID) {
			foreach ($associationHubspotIds as $associationHubspotId) {
				if (
					$contactType == 'AgencyContact' &&
					$associationHubspotId != self::$travellerHubspotId
				) {
					return $associationHubspotId;
				} elseif (
					$contactType == 'Traveller' &&
					$associationHubspotId != $this->agencyContactHubspotId
				) {
					return $associationHubspotId;
				}
			}
			// Wenn es nur den Traveller als Kontakt gibt (Nur den Agenturmitarbeiter geht nicht, weil es immer ein
			// Traveller gibt zu einer Buchung
			// (Die Zuweisung zum Traveller soll nie gelöscht werden)
			return false;
		}

		return reset($associationHubspotIds);
	}

}