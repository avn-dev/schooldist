<?php

namespace TsRdstation\Handler\ParallelProcessing;

use Core\Enums\AlertLevel;
use Core\Handler\ParallelProcessing\TypeHandler;

/**
 * Class AgencyTransfer Parallelprocessing - Hubspot Transfer-Handler für Agenturen und Agenturmitarbeiter
 * @package TsHubspot\Handler
 */
class SyncPayment extends TypeHandler {

	/**
	 * @inheritdoc
	 */
	public function execute(array $aData, $bDebug = false) {

		$oInquiryPayment = \Ext_Thebing_Inquiry_Payment::getInstance($aData['item_id']);
		$oUser = \User::getInstance($aData['user_id']);
		
		$oInquiry = $oInquiryPayment->getInquiry();
			
		$oLog = \Log::getLogger('api', 'rdstation');
		
		try {
		
			$oAccessToken = \TsRdstation\Service\RDStation::getAccessToken();

			$oService = new \TsRdstation\Service\RDStation($oAccessToken);
			$oService->syncPayment($oInquiryPayment, (boolean)$aData['create']);

			if($oUser->exist()) {
				\Core\Service\NotificationService::sendToUser($oUser, sprintf(\L10N::t('Payment from student "%s" has been successfully synchronized with RD Station!', 'TS » Apps » RD Station'), $oInquiry->getFirstTraveller()->getCustomerNumber()), AlertLevel::SUCCESS);
			}
			
			$oLog->info('Sync payment success', $aData);
			
		} catch(\Throwable $e) {

			$aInfo = [$aData, $e->getMessage()];
			if($e instanceof \GuzzleHttp\Exception\ClientException) {
				$aInfo[] = $e->getResponse()->getBody()->getContents();
			}
			$oLog->error('Failure', $aInfo);
			
			if($oUser->exist()) {
				\Core\Service\NotificationService::sendToUser($oUser, sprintf(\L10N::t('Payment from student "%s" could not be synchronized with RD Station (%s)!', 'TS » Apps » RD Station'), $oInquiry->getFirstTraveller()->getCustomerNumber(), $e->getMessage()), AlertLevel::DANGER);
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
		return \L10N::t('RD Station: Synchronize payment', 'TS » Apps » RD Station');
	}

}
