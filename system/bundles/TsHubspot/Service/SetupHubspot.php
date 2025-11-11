<?php

namespace TsHubspot\Service;

use TsHubspot\Service\Helper\General;
use function mysql_xdevapi\getSession;
use TsHubspot\Service\Exceptions\ApiException;
use SevenShores\Hubspot\Http\Response;

/**
 * Class SetupHubspot
 *
 * Setup für die erste Synchronisation zwischen Hubspot und Fidelo, damit beide System die gleichen
 * Daten haben.
 *
 * @package TsHubspot\Service
 */
class SetupHubspot extends Api {

	/**
	 * Initialisiert die Synchronisierung zwischen Hubspot und Fidelo und andersrum.
	 *
	 * @return void
	 * @throws ApiException
	 */
	public function init() {

		try {
			General::increaseHubspotAPILimitCache();
			$response = $this->oHubspot->apiRequest([
				'method' => 'get',
				'path' => '/account-info/v3/details',
			]);

			$iPortalId = json_decode($response->getBody()->getContents(), true)['portalId'];

		} catch (\Throwable $exception) {
			if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
				$errorMessage = $exception->getResponseBody();
			} else {
				$errorMessage = $exception->getMessage();
			}
			$this->oLogger->error('Request failed!', [$errorMessage]);

			throw $exception;
		}

		$aParams = [
			'license' => \System::d('license'),
			'portal_id' => $iPortalId
		];

		$sUrl = 'https://fidelo.com/fidelo/api/hubspot';

		$hCurl = curl_init($sUrl);
		curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($hCurl, CURLOPT_POST, true);
		curl_setopt($hCurl, CURLOPT_POSTFIELDS, $aParams);
		$sJsonResponse = curl_exec($hCurl);

		$aResponse = json_decode($sJsonResponse, true);

		if (!$aResponse['success']) {
			$this->oLogger->addError('Fidelo API Request failed!', [$aResponse['message']]);
			throw new \Exception($aResponse['message']);
		}

		$this->initAgencies();
	}

	public function initAgencies() {

		$fideloAgencies = \Ext_Thebing_Agency::query()
			->orderBy('ext_1')
			->where('status', 1)
			->get();

		$helper = new General();

		// Bzw. hubspotCompanies
		$hubspotAgencies = $helper->getAllHubspotAgencies($this->oHubspot);

		foreach ($fideloAgencies as $fideloAgency) {
			$agencyFoundInHubspot = false;
			$fideloAgencyName = $fideloAgency->getName(true);
			$fideloAgencyAbbreviation = $fideloAgency->getName();

			foreach ($hubspotAgencies as $hubspotAgencyId => $hubspotAgencyName) {

				if (
					str_contains($fideloAgencyName, $hubspotAgencyName) ||
					str_contains($fideloAgencyAbbreviation, $hubspotAgencyName)
				) {
					// Agentur in Hubspot gefunden

					$oldHubspotId = $helper->findHubspotIdByEntity($fideloAgency);
					if (!empty($oldHubspotId)) {
						$helper->deleteHubspotId($oldHubspotId, $fideloAgency);
					}

					// Mappen
					$helper->saveHubspotId($hubspotAgencyId, $fideloAgency);
					$fideloAgency->updateAttribute('hubspot_id', $hubspotAgencyId);
					$agencyFoundInHubspot = true;
					break;
				}
			}
			if (!$agencyFoundInHubspot) {
				$agencyIdsNotFoundInHubspot[] = $fideloAgency->id;
			}
		}

		if (!empty($agencyIdsNotFoundInHubspot)) {
			$conf = \Ext_TS_Config::getInstance();
			$conf->set('hubspot_agency_ids_not_found_in_hubspot', $agencyIdsNotFoundInHubspot);
		}
	}

}