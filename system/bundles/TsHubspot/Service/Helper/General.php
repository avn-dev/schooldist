<?php

namespace TsHubspot\Service\Helper;

use Core\Exception\ParallelProcessing\RewriteException;
use Core\Facade\Cache;
use Core\Helper\DateTime;
use Ext_TC_Flexibility;
use TsHubspot\Service\Mapping;
use TsHubspot\Service\Services;

/**
 *
 * Generelle Helperklasse
 *
 * @package TsHubspot\Service\Helper
 */
class General {

	public $existingProperties;

	public $existingValidationRules;

	public $properties;

	public $travellerEmail;

	const HUBSPOT_CUSTOM_OBJECT_IDENTIFIER = '2-';

	const SELECT_CREATEHUBSPOTCOMPANY_ID = '-1';

	const HUBSPOT_API_LIMIT_TTL = 10;

	const HUBSPOT_API_LIMIT_REQUESTS = 70;

	const HUBSPOT_API_LIMIT_CACHE_KEY = 'hubspot_api_limit';

	public function saveHubspotId($hubspotId, $entity, $travellerHubspotId = null) {

		$data = [
			'hubspot_id' => $hubspotId,
			'entity' => get_class($entity),
			'entity_id' => $entity->id
		];

		if ($travellerHubspotId != null) {
			$data['traveller_hubspot_id'] = $travellerHubspotId;
		}

		try {
			\DB::insertData('ts_hubspot_ids', $data);
		} catch (\Throwable $e) {
			// Wenn der Eintrag schon existiert
		}
	}

	public function deleteHubspotId($hubspotId, $entity) {

		$query = "
			DELETE FROM
				`ts_hubspot_ids`
			WHERE
				`hubspot_id` = :hubspot_id AND
				`entity` = :entity AND
				`entity_id` = :entity_id 
		";

		$queryValues = [
			'hubspot_id' => $hubspotId,
			'entity' => get_class($entity),
			'entity_id' => $entity->id,
		];

		\DB::executePreparedQuery($query, $queryValues);
	}

	public function findHubspotIdByEntity($entity): ?string {

		$sSql = "
			SELECT
				`ts_h_i`.`hubspot_id`
			FROM
				`ts_hubspot_ids` `ts_h_i` 
			WHERE
				`ts_h_i`.`entity` = :entity AND
				`ts_h_i`.`entity_id` = :entity_id
	   ";

		return \DB::getQueryOne($sSql, ['entity' => get_class($entity), 'entity_id' => $entity->id]);
	}

	public function findTravellerHubspotIdByInquiry($inquiry) {
		$sSql = "
			SELECT
				`ts_h_i`.`traveller_hubspot_id`
			FROM
				`ts_hubspot_ids` `ts_h_i` 
			WHERE
				`ts_h_i`.`entity` = :entity AND
				`ts_h_i`.`entity_id` = :entity_id
	   ";

		return \DB::getQueryOne($sSql, ['entity' => 'Ext_TS_Inquiry', 'entity_id' => $inquiry->id]);
	}

	public static function findEntityIdByHubspotIdAndEntity($hubspotId, $entity): ?string {

		$sSql = "
			SELECT
				`ts_h_i`.`entity_id`
			FROM
				`ts_hubspot_ids` `ts_h_i` 
			WHERE
				`ts_h_i`.`hubspot_id` = :hubspot_id AND
				`ts_h_i`.`entity` = :entity
	   ";

		return \DB::getQueryOne($sSql, [ 'hubspot_id' => $hubspotId, 'entity' => $entity]);
	}

	public function setExistingPropertiesCustomObjects($hubspotObjectTypeId, $api) {
		$this::increaseHubspotAPILimitCache();
		$hubspotObject = $api->crm()->schemas()->CoreApi()->getById($hubspotObjectTypeId);
		$this->existingProperties = $hubspotObject->getProperties();
	}

	public function addProperty($propertyKey, $value) {

		// Damit beim updaten der gelöschte Wert auch gelöscht wird
		if (
			$value === '0000-00-00' ||
			empty($value)
		) {
			$value = '';
		}

		if (!empty($propertyKey)) {
			$property = $this->checkIfPropertyExistsInHubSpot($propertyKey);
			if ($property) {
				// Validieren und hinzufügen
				$this->validatePropertyAdding($propertyKey, $value, $property);
			}
		}
	}

	public function checkIfPropertyExistsInHubSpot($propertyKey) {

		foreach ($this->existingProperties as $property) {
			if (is_array($property)) {
				$propertyName = $property['name'];
			} else {
				$propertyName = $property->getName();
			}

			// Wenn die Property existiert
			if ($propertyName == $propertyKey) {
				return $property;
			}
		}

		return false;
	}

	public function getProperties() {
		return $this->properties;
	}

	public function addFilter(&$filterArray, $value, $propertyName, $operator = 'EQ') {

		if (
			// Das Email Array wird erst weitergegeben, wenn es eine Email gibt, also muss keine Überprüfung erfolgen
			// (einziger Fall für $operator == 'IN')
			$operator == 'IN' ||
			strlen($value) > 0
		) {
			$filter = new \HubSpot\Client\Crm\Contacts\Model\Filter();
			if ($operator == 'IN') {
				$filter->setValues($value);
			} else {
				$filter->setValue($value);
			}
			$filter->setOperator($operator);
			$filter->setPropertyName($propertyName);

			$filterArray[] = $filter;
		}
	}

	public static function formatDate($value, $format) {

		if ($value == '0000-00-00') {
			$value = '';
		}

		$return = '';
		if (!empty($value)) {
			$dateTime = new \DateTime($value);
			$dateFormat = str_replace('%', '', $format);
			$return = $dateTime->format($dateFormat);
		}

		return $return;
	}

	public static function formatTime($value) {

		$return = '';
		if (!empty($value)) {
			$dateTime = new \DateTime($value);
			$return = $dateTime->format('H:i');
		}

		return $return;
	}

	/**
	 * @param $service
	 * Weitere Eigenschaften aus den externen App Einstellungen hinzufügen
	 * @return void
	 */
	public function addAllGivenProperties($service) {
		$serviceClassName = get_class($service);

		$serviceSingular = $this->getServiceSingular($serviceClassName);

		// Nur für customfields relevant also auch nur für Kurse
		$language = $this->getServiceLanguage($service, $serviceClassName);

		// Felder mit Getter mappen für getValueFromField()
		$mapping = new Mapping();
		$serviceFields = $mapping->getFieldsByService($serviceSingular);
		$conf = \Ext_TS_Config::getInstance();
		$hubspotFieldMappings = $conf->{'hubspot_'.$serviceSingular.'_fields'};

		// Individuelle Felder
		if (
			$serviceSingular === 'inquiry' ||
			$serviceSingular === 'course' ||
			$serviceSingular === 'agency'
		) {
			$hubspotCustomFieldMappings = $conf->{'hubspot_'.$serviceSingular.'_customfields'};

			foreach ($hubspotCustomFieldMappings as $hubspotCustomFieldMapping) {
				$this->prepareAddProperty(
					$hubspotCustomFieldMapping,
					$hubspotCustomFieldMapping['fidelo_custom_field_id'],
					$service,
					$language,
					'customfield'
				);
			}
		}

		foreach ($hubspotFieldMappings as $hubspotFieldMapping) {
			$this->prepareAddProperty(
				$hubspotFieldMapping,
				$hubspotFieldMapping['fidelo_field'],
				$service,
				$language,
				'field',
				$serviceFields
			);
		}

		// Zusatzleistungen
		if (
			$serviceSingular === 'course' ||
			$serviceSingular === 'accommodation'
		) {
			// Wenn man hier landet, heißt das, dass Hubspot Objekt ist nicht "deals".
			$serviceAdditionalServicesMappings = $conf->{'hubspot_'.$serviceSingular.'_additional_services'};
			foreach ($serviceAdditionalServicesMappings as $serviceAdditionalServicesMapping) {
				$this->prepareAddProperty(
					$serviceAdditionalServicesMapping,
					$serviceAdditionalServicesMapping['fidelo_additional_service_id'],
					$service,
					$language,
					'additionalservice',
				);
			}
		}

		// Der Block: Wenn der Deal als Objekt ausgewählt ist für die Leistungen, dann dem Deal Property Array auch diese
		// Werte hinzufügen.
		if ($serviceSingular === 'inquiry') {
			$servicesForMapping = ['InquiryCourses', 'InquiryAccommodations', 'InquiryTransfers', 'InquiryInsurances', 'InquiryPayments'];
			foreach ($servicesForMapping as $serviceForMapping) {
				$serviceUpdateClassString = '\TsHubspot\Service\\'.$serviceForMapping;
				$hubspotObjectId = \System::d($serviceUpdateClassString::HUBSPOT_OBJECT_KEY, '');
				if ($hubspotObjectId === 'deals') {
					$fieldPropertiesKey = $serviceUpdateClassString::HUBSPOT_FIELD_PROPERTIES_KEY;
					$serviceFields = $mapping->getFieldsByService($serviceUpdateClassString::SERVICE_STRING);

					$serviceFieldMappings = $conf->$fieldPropertiesKey;
					$servicesByInquiry = $serviceUpdateClassString::getServicesByInquiry($service);

					foreach ($serviceFieldMappings as $serviceFieldMapping) {
						$serviceIterationAmount = 1;
						foreach ($servicesByInquiry as $serviceByInquiry) {
							$this->prepareAddProperty(
								$serviceFieldMapping,
								$serviceFieldMapping['fidelo_field'],
								$serviceByInquiry,
								$language,
								'field',
								$serviceFields,
								$serviceIterationAmount
							);
							$serviceIterationAmount++;
						}
					}

					if ($serviceForMapping === 'InquiryCourses') {
						$customfieldPropertiesKey = $serviceUpdateClassString::HUBSPOT_CUSTOMFIELD_PROPERTIES_KEY;
						$serviceCustomFieldMappings = $conf->$customfieldPropertiesKey;

						foreach ($serviceCustomFieldMappings as $serviceCustomFieldMapping) {
							$serviceIterationAmount = 1;

							foreach ($servicesByInquiry as $serviceByInquiry) {
								$this->prepareAddProperty(
									$serviceCustomFieldMapping,
									$serviceCustomFieldMapping['fidelo_custom_field_id'],
									$serviceByInquiry,
									$language,
									'customfield',
									serviceIterationAmount: $serviceIterationAmount
								);
								$serviceIterationAmount++;
							}
						}
					}

					if (
						$serviceForMapping === 'InquiryCourses' ||
						$serviceForMapping === 'InquiryAccommodations'
					) {
						$additionalServicesPropertiesKey = $serviceUpdateClassString::HUBSPOT_ADDITIONAL_SERVICES_PROPERTIES_KEY;
						$serviceAdditionalServicesMappings = $conf->$additionalServicesPropertiesKey;

						foreach ($serviceAdditionalServicesMappings as $serviceAdditionalServicesMapping) {
							$serviceIterationAmount = 1;
							foreach ($servicesByInquiry as $serviceByInquiry) {
								$this->prepareAddProperty(
									$serviceAdditionalServicesMapping,
									$serviceAdditionalServicesMapping['fidelo_additional_service_id'],
									$serviceByInquiry,
									$language,
									'additionalservice',
									serviceIterationAmount: $serviceIterationAmount
								);
								$serviceIterationAmount++;
							}
						}
					}
				}
			}

			// Aktivität gebucht
			$hubspotActivityMappings = $conf->hubspot_activity_mapping;
			foreach ($hubspotActivityMappings as $hubspotActivityMapping) {
				$this->prepareAddProperty(
					$hubspotActivityMapping,
					$hubspotActivityMapping['fidelo_activity_id'],
					$service,
					$language,
					'activity'
				);
			}
		}
	}

	public function prepareAddProperty($fieldMapping, $fieldIdentifier, $service, $language, $fieldType, $serviceFields = [], $serviceIterationAmount = 1) {
		if ($fieldIdentifier !== '') {
			$propertyKey = $fieldMapping['hubspot_property_name_'.$serviceIterationAmount];
			if (!empty($propertyKey)) {
				$propertyValue = $this->getValueFromField($service, $fieldIdentifier, $language, $fieldType, $serviceFields[$fieldIdentifier]);
				$this->addProperty($propertyKey, $propertyValue);
			}
		}
	}

	public function getValueFromField($service, $fieldIdentifier, $language, $fieldType, $field = null) {
		if ($fieldType === 'customfield') {
			if (\Ext_TC_Flexibility::getInstance($fieldIdentifier)->type == Ext_TC_Flexibility::TYPE_CHECKBOX) {
				$format = false;
			} else {
				$format = true;
			}
			return $service->getFlexValue($fieldIdentifier, $language, null, $format);
		} elseif ($fieldType === 'field') {
			if ($fieldIdentifier === 'email') {
				return $this->travellerEmail;
			}

			return $field->getGetter()($service);
		} elseif ($fieldType === 'activity') {
			$journeyActivities = $service->getActivities();
			foreach ($journeyActivities as $journeyActivity) {
				if ($journeyActivity->getActivity()->id == $fieldIdentifier) {
					return true;
				}
			}
		} elseif ($fieldType === 'additionalservice') {
			foreach ($service->additionalservices as $additionalServiceId) {
				if ($additionalServiceId == $fieldIdentifier) {
					return true;
				}
			}
		}
	}

	public function getServiceSingular(string $serviceClassName) {

		if ($serviceClassName == \Ext_TS_Inquiry::class) {
			return 'inquiry';
		} else if ($serviceClassName == \Ext_Thebing_Agency::class) {
			return 'agency';
		} else if ($serviceClassName == \Ext_Thebing_Inquiry_Payment::class) {
			return 'payment';
		} else {
			return strtolower(str_replace('Ext_TS_Inquiry_Journey_', '', $serviceClassName));
		}
	}

	public function getServiceLanguage($service, string $serviceClassName) {

		if ($serviceClassName == \Ext_Thebing_Agency::class) {
			return $service->getLanguage();
		} elseif ($serviceClassName == \Ext_Thebing_Inquiry_Payment::class) {
			return $service->getInquiry()->getSchool()->getLanguage();
		} else {
			return $service->getJourney()->getSchool()->getLanguage();
		}
	}

	public static function getExistingProperties($objectType, $api) {
		self::increaseHubspotAPILimitCache();
		return json_decode($api->apiRequest([
			'method' => 'get',
			'path' => '/crm/v3/properties/'.$objectType,
		])->getBody()->getContents(), true)['results'];
	}

	public static function getExistingValidationRules($objectTypeId, $api) {
		self::increaseHubspotAPILimitCache();
		return json_decode($api->apiRequest([
			'method' => 'get',
			'path' => '/crm/v3/property-validations/'.$objectTypeId,
		])->getBody()->getContents(), true)['results'];
	}

	public function setExistingPropertiesAndValidationRules($objectType, $api): void
	{
		$this->existingProperties = $this::getExistingProperties($objectType, $api);
		$this->existingValidationRules = $this::getExistingValidationRules($objectType, $api);
	}

	public function validatePropertyAdding($propertyKey, $value, $property): void
	{

		if (is_array($property)) {
			$readOnly = $property['modificationMetadata']['readOnlyValue'];
			$propertyOptions = $property['options'];
			$propertyType = $property['type'];
			$propertyLabel = $property['label'];
			$propertyName = $property['name'];
		} else {
			$readOnly = $property->getModificationMetadata()->getReadOnlyValue();
			$propertyOptions = $property->getOptions();
			$propertyType = $property->getType();
			$propertyLabel = $property->getLabel();
			$propertyName = $property->getName();
		}

		if ($readOnly) {
			\Log::getLogger('api', 'hubspot')->error(
				'Validation failed for Property, still continuing...',
				[
					'Property is Read-Only: '.$propertyLabel
				]
			);
			return;
		}

		// Wenn ein Validierungsfehler von den eingestellten Regeln kommt, dann das Property nicht zum Array hinzufügen.
		// -> Es wird dann einfach weggelassen und nicht gesynced, besser als eine Exception und alles hört auf.
		if (!$this->validateWithValidationRules($propertyName, $value, $propertyLabel, $propertyType)) {
			return;
		}

		if (
			empty($propertyOptions) &&
			$propertyType == 'string'
		) {
			// Success
			$this->properties[$propertyKey] = $value;
			return;
		} elseif (
			$propertyType == 'number' &&
			is_numeric($value)
		) {
			// Success
			$this->properties[$propertyKey] = (float)$value;
			return;
		} elseif (!empty($propertyOptions)) {
			// Bei Selects / Radiobuttons / Checkboxen...
			foreach ($propertyOptions as $option) {

				if (is_array($option)) {
					$optionValue = $option['value'];
					$optionLabel = $option['label'];
				} else {
					$optionValue = $option->getValue();
					$optionLabel = $option->getLabel();
				}

				if (
					(
						$optionValue === 'Yes' ||
						$optionValue === 'yes' ||
						$optionValue === 'true'
					) ||
					(
						$optionLabel === 'Yes' ||
						$optionLabel === 'yes' ||
						$optionLabel === 'true'
					) &&
					$value === true
				) {
					// Bei Dropdowns, ist bei expanish zum Beispiel so und die sind wohl keine Ausnahme was das angeht.
					// Weil die "nein" Option teilweise (oder immer) anders heißt kann ich hier schlecht den Wert entfernen wieder.
					$this->properties[$propertyKey] = $optionValue;
					return;
				}

				// Checkbox-Werte werden nicht formatiert -> für diesen Fall
				if (
					(
						$optionValue === 'true' &&
						$value === '1'
					) ||
					(
						$optionValue === 'false' &&
						$value === ''
					) ||
					$optionValue == $value ||
					$optionLabel == $value
				) {
					// Die ersten beiden Abfragen sind für einfache Checkboxen, sonst
					// "Wenn der Wert existiert"

					// Bei einfachen Checkboxen muss man Hubspot "true" oder "false" als Wert geben.
					$this->properties[$propertyKey] = $optionValue;
					return;
				}
			}
			// Zu diesem Zeitpunkt gibt es den Wert nicht zur Auswahl bei Hubspot, also ein Leereintrag,
			// weil sonst bei einem Update ein falscher Wert noch existiert (lieber kein Wert als ein falscher)
			$this->properties[$propertyKey] = null;
			\Log::getLogger('api', 'hubspot')->error('Validation failed for Property, still continuing...', ['Option '.$value.' not found for: '.$propertyLabel]);
			return;
		} elseif ($propertyType == 'datetime' || $propertyType == 'date') {

			if ($value != '') {
				try {
					$dateTime = new DateTime($value);
					// Datum formatiert nach Hubspot Richtlinien
					$value = $dateTime->format('Y-m-d\TH:i:s\Z');
				} catch (\Throwable $e) {
					\Log::getLogger('api', 'hubspot')->error('Error creating DateTime for Property Validation', [$e->getMessage()]);
					return;
				}
			}

			$this->properties[$propertyKey] = $value;
			return;
		}

		// Zu dem Zeitpunkt liegt es nicht an existierenden Optionen, readOnly oder irgend was von oben.
		\Log::getLogger('api', 'hubspot')->error('Validation failed for Property, still continuing...', ['Property: '.$propertyLabel, 'Value: '.$value, 'Type: '.$propertyType]);
	}

	public function validateWithValidationRules($propertyName, $value, $propertyLabel, $propertyType) {
		foreach ($this->existingValidationRules as $existingValidationRule) {
			// Wenn es Validierungsregeln für diese Eigenschaft gibt
			if ($existingValidationRule['propertyName'] === $propertyName) {
				$validationRules = $existingValidationRule['propertyValidationRules'];

				$validationNotSuccesful = false;
				foreach ($validationRules as $validationRule) {
					$ruleArgument = reset($validationRule['ruleArguments']);
					$ruleType = $validationRule['ruleType'];
					switch ($ruleType) {
						case 'MIN_NUMBER':
							$validationNotSuccesful = (float)$value < (float)$ruleArgument;
							break;
						case 'MAX_NUMBER':
							$validationNotSuccesful = (float)$value > (float)$ruleArgument;
							break;
						case 'DECIMAL':
							if (str_contains($value, '.')) {
								$numberParts = explode('.', $value);
							} elseif (str_contains($value, ',')) {
								$numberParts = explode(',', $value);
							} else {
								break;
							}

							// Stellen Rechts vom Komma
							$decimalPlaces = $numberParts[1];
							$validationNotSuccesful = strlen($decimalPlaces) > (int)$ruleArgument;
							break;
						case 'MIN_LENGTH':
							$validationNotSuccesful = strlen($value) < (int)$ruleArgument;
							break;
						case 'MAX_LENGTH':
							$validationNotSuccesful = strlen($value) > (int)$ruleArgument;
							break;
						case 'ALPHANUMERIC':
							if ($ruleArgument === 'NUMERIC_ONLY') {
								$validationNotSuccesful = !is_numeric($value);
							}
							break;
						case 'SPECIAL_CHARACTERS':
							if ($ruleArgument === 'NOT_ALLOWED') {
								$validationNotSuccesful = preg_match('/[^a-zA-Z0-9]/', $value);
							}
							break;
						case 'REGEX':
							$validationNotSuccesful = !preg_match($ruleArgument, $value);
							break;
						case 'PHONE_NUMBER_WITH_EXPLICIT_COUNTRY_CODE':
							// Erstmal weggelassen, zu viele Dinge zu validieren
							break;
					}

					// Wenn eine Validierungsregel nicht erfüllt wurde
					if ($validationNotSuccesful) {
						\Log::getLogger('api', 'hubspot')->error(
							'Validation failed for Property because of Validation Rule, still continuing...',
							[
								'Property: '.$propertyLabel,
								'Value: '.$value,
								'Type: '.$propertyType,
								'Validation Ruletype: '.$ruleType,
								'Validation Ruleargument: '.$ruleArgument,
							]
						);

						// Validierung hier nicht successful
						return false;
					}
				}
			}
		}

		// Validierung hier successful
		return true;
	}

	// Bzw. Hubspot Companies
	public function getAllHubspotAgencies($api) {
		self::increaseHubspotAPILimitCache();
		$allHubspotAgencies = json_decode($api->apiRequest([
			'method' => 'get',
			'path' => '/companies/v2/companies/paged?limit=250&properties=name&properties=abbreviation&properties=short',
		])->getBody()->getContents(), true)['companies'];

		$agencyName = '';
		$formattedHubspotAgencies = [];
		foreach ($allHubspotAgencies as $hubspotAgency) {
			$properties = $hubspotAgency['properties'];
			$name = $properties['name'];
			$abbreviation = $properties['abbreviation'];
			$short = $properties['short'];
			if (!empty($name)) {
				$agencyName = $name['value'];

				if (!empty($abbreviation)) {
					$agencyName .= '('.$abbreviation['value'].')';
				} else if (!empty($short)) {
					$agencyName = '('.$short['value'].')';
				}
			} else {
				if (!empty($abbreviation)) {
					$agencyName = $abbreviation['value'];
				} else if (!empty($short)) {
					$agencyName = $short['value'];
				}
			}

			// Array mit nur benötigten Informationen erstellen, damit ordentlich sortiert werden kann.
			$formattedHubspotAgencies[$hubspotAgency['companyId']] = $agencyName;
		}
		asort($formattedHubspotAgencies);

		return $formattedHubspotAgencies;
	}

	public static function checkHubspotAPILimit() {
		$count = self::increaseHubspotAPILimitCache();
		if ($count >= self::HUBSPOT_API_LIMIT_REQUESTS) {
			$errorMessage = 'Hubspot API Limit reached!';
			\Log::getLogger('api', 'hubspot')->error($errorMessage);
			throw new RewriteException($errorMessage);
		}
	}

	public static function increaseHubspotAPILimitCache() {
		// Um 1 hochzählen und 10 Sekunden TTL (Hubspot Limit)
		return Cache::increment(self::HUBSPOT_API_LIMIT_CACHE_KEY, 1, 1, self::HUBSPOT_API_LIMIT_TTL);
	}

	public function prepareServiceUpdate($entity, $hubspotObjectKey, $hubspotAPIObject) {

		$hubspotObjectId = \System::d($hubspotObjectKey);
		$hubspotObjectTypeId = self::HUBSPOT_CUSTOM_OBJECT_IDENTIFIER.$hubspotObjectId;
		// Zum Validieren in der validatePropertyAdding()
		$this->setExistingPropertiesCustomObjects($hubspotObjectTypeId, $hubspotAPIObject);
		$this->existingValidationRules = $this::getExistingValidationRules($hubspotObjectTypeId, $hubspotAPIObject);

		//Eigenschaften aus den externen App Einstellungen hinzufügen
		$this->addAllGivenProperties($entity);

		$service = new Services();
		return $service->update($entity, $hubspotObjectKey, $this->properties);
	}

}