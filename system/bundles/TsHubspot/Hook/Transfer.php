<?php

namespace TsHubspot\Hook;

use TsHubspot\Service\Inquiry;

class Transfer extends \Core\Service\Hook\AbstractHook {

	public function run($mData) {

		if(\TsHubspot\Handler\ExternalApp::isActive()) {

			$hubspotHelper = new \TsHubspot\Service\Helper\General();

			if (
				$mData instanceof \Ext_Thebing_Agency &&
				$mData->hubspot_id != $hubspotHelper::SELECT_CREATEHUBSPOTCOMPANY_ID
			) {
				$hubspotHelper = new \TsHubspot\Service\Helper\General();

				$oldHubspotId = $hubspotHelper->findHubspotIdByEntity($mData);

				if (!empty($oldHubspotId)) {
					$hubspotHelper->deleteHubspotId($oldHubspotId, $mData);
				}

				$hubspotHelper->saveHubspotId($mData->hubspot_id, $mData);
			}

			$oUser = \Access::getInstance();

			/** @var $oStackRepository \Core\Entity\ParallelProcessing\StackRepository */
			$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();

			$oStackRepository->writeToStack('ts-hubspot/transfer', [
				'entity_id' => $mData->id,
				'entity_classname' => get_class($mData),
				'user_id' => $oUser->id,
				// Bei Hubspot-Kontaktsuche
				'traveller_hubspot_id' => Inquiry::$travellerHubspotId
			], 10);
		}
	}

}

