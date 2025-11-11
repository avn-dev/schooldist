<?php

namespace TsRdstation\Handler\ParallelProcessing;

use Core\Enums\AlertLevel;
use Core\Handler\ParallelProcessing\TypeHandler;

/**
 * Class AgencyTransfer Parallelprocessing - Hubspot Transfer-Handler für Agenturen und Agenturmitarbeiter
 * @package TsHubspot\Handler
 */
class SyncInquiry extends TypeHandler {

	/**
	 * @inheritdoc
	 */
	public function execute(array $aData, $bDebug = false) {

		$oInquiry = \Ext_TS_Inquiry::getInstance($aData['item_id']);
		$oUser = \User::getInstance($aData['user_id']);
		
		$oLog = \Log::getLogger('api', 'rdstation');
		
		try {
			
			$oAccessToken = \TsRdstation\Service\RDStation::getAccessToken();

			$oService = new \TsRdstation\Service\RDStation($oAccessToken);
			$oService->syncInquiry($oInquiry, (boolean)$aData['create']);

			if($oUser->exist()) {
				\Core\Service\NotificationService::sendToUser($oUser, sprintf(\L10N::t('Booking from student "%s" has been successfully synchronized with RD Station!', 'TS » Apps » RD Station'), $oInquiry->getFirstTraveller()->getCustomerNumber()), AlertLevel::SUCCESS);
			}
			
			$oLog->info('Sync booking success', $aData);
			
		} catch(\Exception | \Error $e) {

			$aInfo = [$aData, $e->getMessage()];
			if($e instanceof \GuzzleHttp\Exception\ClientException) {
				$aInfo[] = $e->getResponse()->getBody()->getContents();
			}
			$oLog->error('Failure', $aInfo);
			
			if($oUser->exist()) {
				\Core\Service\NotificationService::sendToUser($oUser, sprintf(\L10N::t('Booking from student "%s" could not be synchronized with RD Station (%s)!', 'TS » Apps » RD Station'), $oInquiry->getFirstTraveller()->getCustomerNumber(), $e->getMessage()), AlertLevel::DANGER);
			}

			#throw $e;
			
		}

	}

	/**
	 * Gibt den Namen für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('RD Station: Synchronize student', 'TS » Apps » RD Station');
	}

}
