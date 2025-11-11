<?php

namespace TsHubspot\Handler;

use Core\Handler\SessionHandler as Session;
use TsHubspot\Service\Api;
use TsHubspot\Service\Helper\General;
use TsHubspot\Service\Mapping;

class ExternalApp extends \TcExternalApps\Interfaces\ExternalApp {

	const APP_NAME = 'hubspot';

	const HUBSPOT_OBJECTS_CACHE_KEY = 'hubspot_custom_objects';

	/**
	 * @var Session
	 */
	protected $oSession;

	protected $oSmarty;

	protected $config;


	/**
	 * @return string
	 */
	public function getTitle() : string {
		return \L10N::t('Hubspot');
	}

	/**
	 * @return string
	 */
	public function getDescription() : string {
		return \L10N::t('Hubspot - Beschreibung');
	}

	public function getIcon(): string {
		return 'fab fa-hubspot';
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::CRM;
	}

	/**
	 * @return string $sHtml
	 */
	public function getContent() : ?string {

		$this->oSmarty = new \SmartyWrapper();

		// Gibt es schon eine Verbindung?
		if(empty(\System::d('hubspot_access_token'))) {
			$connectionActive = false;
		} else {
			$this->config = $this->request->input('config');

			$connectionActive = true;

			$api = new Api();

			$customObjects = \WDCache::get(self::HUBSPOT_OBJECTS_CACHE_KEY);

			if ($customObjects === null) {
				$customObjectSchemas = $api->oHubspot->crm()->schemas()->CoreApi()->getAll()->getResults();
				$customObjects['deals'] = '-- Deals --';
				foreach ($customObjectSchemas as $customObjectSchema) {
					$customObjects[$customObjectSchema->getId()] = $customObjectSchema->getLabels()->getPlural();
				}

				\WDCache::set(self::HUBSPOT_OBJECTS_CACHE_KEY, 300, $customObjects);
			}
			$this->oSmarty->assign('customObjects', $customObjects);

			$services = ['courses', 'accommodations', 'transfers', 'insurances', 'payments', 'activities', 'inquiries', 'agencies'];
			foreach ($services as $service) {
				if (
					$service === 'courses' ||
					$service === 'accommodations'
				) {
					// Zusatzleistungen gibt es (noch?) nur bei Kursen und Unterkünften
					$this->assignExistingAdditionalServicesForSelect($service);
					$this->assignAdditionalServicesRows($service);
				}
				if (
					$service === 'courses' ||
					$service === 'inquiries' ||
					$service === 'agencies'
				) {
					$this->assignExistingCustomFieldsForSelect($service);
					$this->assignCustomFieldRows($service);
				}
				// Gibt es nicht bei Buchungen oder Agenturen aber trotzdem für Spaltenanzahl der Properties
				$this->assignSelectedObject($service);
				$this->assignPropertiesForSelect($service);
				$this->assignExistingFieldsForSelect($service);
				$this->assignFieldRows($service);
			}

			$this->assignMappingRows();
			$this->assignAgencyFields();

			$conf = \Ext_TS_Config::getInstance();
			$helper = new General();

			$this->oSmarty->assign('hubspotAgencyIdsNotFoundInHubspot', $conf->hubspot_agency_ids_not_found_in_hubspot);
			$this->oSmarty->assign('hubspotAgencies', $helper->getAllHubspotAgencies($api->oHubspot));
			$this->oSmarty->assign('activities', \TsActivities\Entity\Activity::getRepository()->getSelectOptions(\Ext_Thebing_School::getSchoolFromSession()));
			$this->oSmarty->assign('hubspotAdditionalMultipleEmails', $this->getHubspotInformation('hubspot_additional_multiple_emails'));
			$this->oSmarty->assign('alreadyExistingContactAction', $this->getHubspotInformation('hubspot_already_existing_contact_action'));
		}

		if($this->oSession === NULL) {
			$this->oSession = Session::getInstance();
		}

		$this->oSmarty->assign('connectionActive', $connectionActive);
		$this->oSmarty->assign('oApp', $this);
		$this->oSmarty->assign('oSession', $this->oSession);
		$this->oSmarty->assign('appKey', self::APP_NAME);

		$sHtml = $this->oSmarty->fetch('@TsHubspot/hubspot.tpl');

		return $sHtml;
	}

	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest) {

		$conf = new \Ext_TS_Config(0, null, true);
		$config = $oRequest->input('config');

		// Wenn das Objekt-Select leer sein sollte, davor aber Properties zu dem Objekt ausgewählt wurden, werden diese
		// Properties nicht auf null gesetzt, sondern die Werte bleiben.
		foreach ($config as $configKey => $configValue) {
			if (
				!str_contains($configKey, 'add_row') &&
				!str_contains($configKey, 'remove_row')
			) {
				if ($configKey == 'hubspot_agencies') {
					// Agenturmapping

					$oldConfig = $conf->hubspot_agencies;
					$helper = new General();

					// Alte Informationen löschen
					foreach ($oldConfig as $rowCount => $oldAgencyRow) {
						if (
							$oldAgencyRow['fidelo_agency_id'] != $configValue[$rowCount]['fidelo_agency_id'] ||
							$oldAgencyRow['hubspot_agency_id'] != $configValue[$rowCount]['hubspot_agency_id']
						) {
							// Wenn sich bei einer Reihe etwas geändert hat, alte Informationen löschen
							$oldAgency = \Ext_Thebing_Agency::getInstance($oldAgencyRow['fidelo_agency_id']);
							$helper->deleteHubspotId($oldAgencyRow['hubspot_agency_id'], $oldAgency);
						}
					}

					foreach ($configValue as $agencyRow) {

						$hubspotAgencyId = $agencyRow['hubspot_agency_id'];
						$fideloAgencyId = $agencyRow['fidelo_agency_id'];
						$agency = \Ext_Thebing_Agency::getInstance($fideloAgencyId);

						if (
							!empty($hubspotAgencyId) &&
							!empty($fideloAgencyId)
						) {
							$oldHubspotId = $helper->findHubspotIdByEntity($agency);

							if (!empty($oldHubspotId)) {
								$helper->deleteHubspotId($oldHubspotId, $agency);
							}
							// Mappen
							$helper->saveHubspotId($hubspotAgencyId, $agency);
							$agency->updateAttribute('hubspot_id', $hubspotAgencyId);
						}
					}
				}

				$conf->set($configKey, $configValue);
			}
		}

		$oSession->getFlashBag()->add('success', \L10N::t('Ihre Einstellungen wurden gespeichert'));
	}

	public function install() {
		
		$sqlFile = file_get_contents(__DIR__.'/../Resources/sql/hubspot.sql');

		$sqlQueries = explode(';', $sqlFile);
		
		foreach($sqlQueries as $sqlQuery) {
			if(empty($sqlQuery)) {
				continue;
			}

//			try {
				\DB::executeQuery($sqlQuery);
//			} catch(\DB_QueryFailedException $e) {
//			}
		}
	
	}

	public function assignPropertiesForSelect($service) {

		if (
			$service === 'inquiries' ||
			$service === 'agencies' ||
			$service === 'activities'
		) {
			$hubspotObjectId = $service;
		} else {
			$hubspotObjectId = $this->getHubspotInformation('hubspot_'.$service);
		}

		if (!empty($hubspotObjectId)) {

			$cacheKey = 'hubspot_properties_'.$hubspotObjectId;

			$existingProperties = \WDCache::get($cacheKey);

			if ($existingProperties === null) {

				$api = new Api();

				if (
					$service === 'inquiries' ||
					$service === 'activities' ||
					$hubspotObjectId === 'deals'
				) {
					$existingProperties = array_merge(
						General::getExistingProperties('deals', $api->oHubspot),
						General::getExistingProperties('contacts', $api->oHubspot)
					);
				} elseif ($service == 'agencies') {
					$existingProperties = General::getExistingProperties('companies', $api->oHubspot);
				} else {
					$hubspotObject = $api->oHubspot->crm()->schemas()->CoreApi()->getById(General::HUBSPOT_CUSTOM_OBJECT_IDENTIFIER . $hubspotObjectId);
					$existingProperties = $hubspotObject->getProperties();
				}

				// Alphabetisch aufsteigend sortieren
				usort($existingProperties, function ($a, $b) {
					return strcasecmp($a['label'], $b['label']);
				});

				\WDCache::set($cacheKey, 300, $existingProperties);
			}

			$this->oSmarty->assign('hubspot' . ucfirst($service) . 'Properties', $existingProperties);
		}

	}

	public function assignExistingCustomFieldsForSelect($serviceString) {

		if ($serviceString == 'inquiries') {
			$inquiry = new \Ext_Ts_Inquiry();
			$this->oSmarty->assign('inquiriesCustomFields', $inquiry->getFlexibleFields());
		} else if ($serviceString == 'agencies') {
			$agency = new \Ext_Thebing_Agency();
			$this->oSmarty->assign('agenciesCustomFields', $agency->getFlexibleFields());
		}
		else {
			// Bei Leistungen

			$hubspotObjectId = $this->getHubspotInformation('hubspot_'.$serviceString);
			if (!empty($hubspotObjectId)) {
				$serviceSingular = $this->getServiceSingular($serviceString);
				$serviceClassString = '\Ext_TS_Inquiry_Journey_'.ucfirst($serviceSingular);
				$service = new $serviceClassString();

				$this->oSmarty->assign($serviceString.'CustomFields', $service->getFlexibleFields());
			}
		}

	}

	public function assignExistingFieldsForSelect($serviceString) {

		// Standardfelder$this->getJoinedObjectChilds('additionalservices')
		// Übersetzung im tpl
		switch ($serviceString) {
			case 'courses':
				$this->oSmarty->assign('courseFields', Mapping::getCourseFieldsForMapping());
				break;
			case 'accommodations':
				$this->oSmarty->assign('accommodationFields', Mapping::getAccommodationFieldsForMapping());
				break;
			case 'transfers':
				$this->oSmarty->assign('transferFields', Mapping::getTransferFieldsForMapping());
				break;
			case 'insurances':
				$this->oSmarty->assign('insuranceFields', Mapping::getInsuranceFieldsForMapping());
				break;
			case 'payments':
				$this->oSmarty->assign('paymentFields', Mapping::getPaymentFieldsForMapping());
				break;
			case 'inquiries':
				$this->oSmarty->assign('inquiryFields', Mapping::getInquiryFieldsForMapping());
				break;
			case 'agencies':
				$this->oSmarty->assign('agencyFields', Mapping::getAgencyFieldsForMapping());
				break;
			case 'activities':
				$this->oSmarty->assign('activityFields', Mapping::getActivityFieldsForMapping());
				break;
		}
	}

	public function assignExistingAdditionalServicesForSelect($serviceString) {
		$this->oSmarty->assign(
			$serviceString.'AdditionalServices',
			\Ext_Thebing_Client::getAdditionalServices(type: $this->getServiceSingular($serviceString), forSelect: true)
		);
	}

	public function assignSelectedObject($service) {

		$hubspotObjectId = $this->getHubspotInformation('hubspot_'.$service);

		if (!empty($hubspotObjectId)) {
			$this->oSmarty->assign('selectedObjectId'.ucfirst($service), $hubspotObjectId);

			// Wenn es ein Hauptobjekt ist (Deals bisher nur), dann 3 Spalten aus den Properties machen, damit 3 Leistungen
			// gespeichert werden können und nicht nur eine. (Es kann beim Hauptobjekt ja nur ein Objekt erstellt werden
			// auch bei mehreren Leistungen)
			if (
				$hubspotObjectId === 'deals' &&
				$service != 'activities'
			) {
				$fieldsColumnAmount = 3;
			} else {
				// Bei activities auch, weil es pro Buchung nur einmal Sinn ergibt.
				$fieldsColumnAmount = 1;
			}
			$this->oSmarty->assign($service.'PropertiesColumnAmount', $fieldsColumnAmount);

		} elseif (
			$service === 'inquiries' ||
			$service === 'agencies' ||
			$service === 'activities'
		) {
			// Da kann man (noch) kein hubspotObject auswählen und somit ist das immer das Hauptobjekt
			$this->oSmarty->assign($service.'PropertiesColumnAmount', 1);
		}
	}

	public function getHubspotInformation($key) {

		// Bei einer Änderung bei dem Objekt-Select oder beim Hinzufügen / Entfernen einer Custom-Feld-Reihe
		// (-> Nicht beim jeweils 1. Aufruf der Seite)
		if ($this->request->has('config')) {
			return $this->config[$key];
		} else {
			try {
				$conf = \Ext_TS_Config::getInstance();
				return $conf->$key;
			} catch (\Throwable) {
				// Wenn es den Wert nicht gibt, alternativ mit $conf->getValue(), ist aber deprecated..
				return '';
			}
		}
	}

	public function assignCustomFieldRows($service) {

		$serviceSingular = $this->getServiceSingular($service);

		$customFields = $this->getHubspotInformation('hubspot_'.$serviceSingular.'_customfields');

		if (empty($customFields)) {
			$customFields = [
				['fidelo_custom_field_id' => '', 'hubspot_property_name_1' => '']
			];
		}

		if (!empty($this->config['add_row_customfields'])) {
			$serviceWhereToAddRow = $this->config['add_row_customfields'];
			if ($serviceWhereToAddRow == $service) {
				$customFields[] = ['fidelo_custom_field_id' => '', 'hubspot_property_name_1' => ''];
			}
		} else if (!empty($this->config['remove_row_customfields'])) {
			$removeRowSettings = $this->config['remove_row_customfields'];
			if ($removeRowSettings['service'] == $service) {
				$rowToRemove = $removeRowSettings['row_count'];
				unset($customFields[$rowToRemove]);
				if (empty($customFields)) {
					$customFields[] = ['fidelo_custom_field_id' => '', 'hubspot_property_name_1' => ''];
				}
			}
		}
		// Indexe wieder zurücksetzen, die sind nicht wichtig, nur wichtig in welcher Reihenfolge die Felder sind.
		$customFields = array_values($customFields);
		$this->oSmarty->assign($service.'CustomFieldRows', $customFields);
	}

	public function getServiceSingular($service)
	{
		if (
			$service == 'inquiries' ||
			$service == 'agencies' ||
			$service == 'activities'
		) {
			$serviceSingular = substr($service, 0, -3);
			$serviceSingular .= 'y';
		} else {
			$serviceSingular = substr($service, 0, -1);
		}

		return $serviceSingular;
	}

	public function assignFieldRows($service) {

		$serviceSingular = $this->getServiceSingular($service);

		$fields = $this->getHubspotInformation('hubspot_'.$serviceSingular.'_fields');

		if (empty($fields)) {
			$fields = [
				['fidelo_field' => '', 'hubspot_property_name_1' => '']
			];
		}

		if (!empty($this->config['add_row_fields'])) {
			$serviceWhereToAddRow = $this->config['add_row_fields'];
			if ($serviceWhereToAddRow === $service) {
				$fields[] = ['fidelo_field' => '', 'hubspot_property_name_1' => ''];
			}
		} else if (!empty($this->config['remove_row_fields'])) {
			$removeRowSettings = $this->config['remove_row_fields'];
			if ($removeRowSettings['service'] === $service) {
				$rowToRemove = $removeRowSettings['row_count'];
				unset($fields[$rowToRemove]);
				if (empty($fields)) {
					$fields[] = ['fidelo_field' => '', 'hubspot_property_name_1' => ''];
				}
			}
		}
		// Indexe wieder zurücksetzen, die sind nicht wichtig, nur wichtig in welcher Reihenfolge die Felder sind.
		$fields = array_values($fields);
		$this->oSmarty->assign($service.'FieldRows', $fields);
	}

	public function assignAdditionalServicesRows($service) {
		$serviceSingular = $this->getServiceSingular($service);

		$additionalServicesRows = $this->getHubspotInformation('hubspot_'.$serviceSingular.'_additional_services');

		if (empty($additionalServicesRows)) {
			$additionalServicesRows = [
				['additional_service_id' => '', 'hubspot_property_name_1' => '']
			];
		}

		$serviceWhereToAddRow = $this->config['add_row_additional_services'];
		if (!empty($serviceWhereToAddRow)) {
			if ($serviceWhereToAddRow === $service) {
				$additionalServicesRows[] = ['additional_service_id' => '', 'hubspot_property_name_1' => ''];
			}
		} else if (!empty($this->config['remove_row_additional_services'])) {
			$removeRowSettings = $this->config['remove_row_additional_services'];
			if ($removeRowSettings['service'] === $service) {
				$rowToRemove = $removeRowSettings['row_count'];
				unset($additionalServicesRows[$rowToRemove]);
				if (empty($additionalServicesRows)) {
					$additionalServicesRows[] = ['additional_service_id' => '', 'hubspot_property_name_1' => ''];
				}
			}
		}

		// Indexe wieder zurücksetzen, die sind nicht wichtig, nur wichtig in welcher Reihenfolge die Felder sind.
		$additionalServicesRows = array_values($additionalServicesRows);
		$this->oSmarty->assign($service.'AdditionalServicesRows', $additionalServicesRows);
	}

	public function assignAgencyFields() {
		// Für das Agency Mapping, ist ein Spezialfall in den externen App-Einstellungen

		$agencyFields = $this->getHubspotInformation('hubspot_agencies');

		if (empty($agencyFields)) {
			$agencyFields[] = ['fidelo_agency_id' => '', 'hubspot_agency_id' => ''];
		}

		if (!empty($this->config['add_row_fields'])) {
			if ($this->config['add_row_fields'] == 'agenciesObjects') {
				$agencyFields[] = ['fidelo_agency_id' => '', 'hubspot_agency_id' => ''];
			}
		} else if (!empty($this->config['remove_row_fields'])) {
			$removeRowSettings = $this->config['remove_row_fields'];
			if ($removeRowSettings['service'] == 'agenciesObjects') {
				$rowToRemove = $removeRowSettings['row_count'];
				if (count($agencyFields) == 1) {
					$agencyFields[$rowToRemove] = ['fidelo_agency_id' => '', 'hubspot_agency_id' => ''];
				} else {
					unset($agencyFields[$rowToRemove]);
				}
			}
		}

		$agencyFields = array_values($agencyFields);
		$this->oSmarty->assign('agencyRows', $agencyFields);
	}

	public function assignMappingRows() {
		// Für das Activity Mapping, ist ein Spezialfall in den externen App-Einstellungen

		$activityMappingRows = $this->getHubspotInformation('hubspot_activity_mapping');

		if (empty($activityMappingRows)) {
			$activityMappingRows = [
				['fidelo_activity_id' => '', 'hubspot_property_name_1' => '']
			];
		}

		if (!empty($this->config['add_row_mappings'])) {
			if ($this->config['add_row_mappings'] == 'activities') {
				$activityMappingRows[] = ['fidelo_activity_id' => '', 'hubspot_property_name_1' => ''];
			}
		} else if (!empty($this->config['remove_row_mappings'])) {
			$removeRowSettings = $this->config['remove_row_mappings'];
			if ($removeRowSettings['service'] == 'activities') {
				$rowToRemove = $removeRowSettings['row_count'];
				if (count($activityMappingRows) == 1) {
					$activityMappingRows[$rowToRemove] = ['fidelo_activity_id' => '', 'hubspot_property_name_1' => ''];
				} else {
					unset($activityMappingRows[$rowToRemove]);
				}
			}
		}

		$this->oSmarty->assign('activityMappingRows', $activityMappingRows);
	}

	public function uninstall() {

		// Verbindung auch noch zurücksetzen
		$hubspotConfigEntries = \DB::table('system_config')->where('c_key', 'LIKE', 'hubspot_%')->get();

		foreach ($hubspotConfigEntries as $hubspotConfigEntry) {
			\System::deleteConfig($hubspotConfigEntry['c_key']);
		}

		\WDCache::delete(ExternalApp::HUBSPOT_OBJECTS_CACHE_KEY);
	}

	public static function isActive(): bool {

		if(
			\TcExternalApps\Service\AppService::hasApp(self::APP_NAME) &&
			!empty(\System::d('hubspot_access_token'))
		) {
			return true;
		}

		return false;
	}

}