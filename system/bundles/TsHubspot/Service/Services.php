<?php

namespace TsHubspot\Service;

use TsHubspot\Service\Helper\General;

class Services extends Api {

	public function update($service, $hubspotObjectKey, $properties) {

		$entityHubspotObject = new \HubSpot\Client\Crm\Objects\Model\SimplePublicObjectInput;

		$entityHubspotObject->setProperties($properties);

		$helper = new General();

		$objectTypeId = $helper::HUBSPOT_CUSTOM_OBJECT_IDENTIFIER.\System::d($hubspotObjectKey);

		$hubspotId = $helper->findHubspotIdByEntity($service);

		try {
			$helper::increaseHubspotAPILimitCache();
			if (!empty($hubspotId)) {
				$this->oHubspot->crm()->objects()->basicApi()->update($objectTypeId, $hubspotId, $entityHubspotObject);
				$newService = false;
			} else {
				$request = $this->oHubspot->crm()->objects()->basicApi()->create($objectTypeId, $entityHubspotObject);
				$hubspotId = $request->getId();
				$helper->saveHubspotId($hubspotId, $service);
				$newService = true;
			}
		} catch (\Throwable $exception) {
			if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
				$errorMessage = $exception->getResponseBody();
			} else {
				$errorMessage = $exception->getMessage();
			}
			$this->oLogger->error('Creating or Updating '.get_class($service).' in Hubspot failed!', [$errorMessage]);

			throw $exception;
		}

		return $newService;
	}
}