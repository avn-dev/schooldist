<?php

namespace TsMoodle\Handler\ParallelProcessing;

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

		$oInquiry = \Ext_TS_Inquiry::getInstance($aData['inquiry_id']);
		$oUser = \User::getInstance($aData['user_id']);
		
		$oLog = \Log::getLogger('api', 'moodle');
		
		try {
			
			$oMoodleService = new \TsMoodle\Service\MoodleWebService\Student($oInquiry->getSchool());
			$oMoodleService->sync($oInquiry);

			if($oUser->exist()) {
				\Core\Service\NotificationService::sendToUser($oUser, sprintf(\L10N::t('Booking "%s" from student "%s" has been successfully synchronized with Moodle!', 'TS » Apps » Moodle'), $oInquiry->getNumber(), $oInquiry->getFirstTraveller()->getCustomerNumber()), AlertLevel::SUCCESS);
			}
			
		} catch(\Exception | \Error $e) {

			$aInfo = [$aData, $e];
			if($e instanceof \MoodleSDK\Util\MoodleException) {
				$aInfo[] = $e->getMethod();
				$aInfo[] = $e->getPayload();
			}
			$oLog->error('Failure', $aInfo);
			
			if($oUser->exist()) {
				\Core\Service\NotificationService::sendToUser($oUser, sprintf(\L10N::t('Booking "%s" from student "%s" could not be synchronized with Moodle (%s)!', 'TS » Apps » Moodle'), $oInquiry->getNumber(), $oInquiry->getFirstTraveller()->getCustomerNumber(), $e->getMessage()), AlertLevel::DANGER);
			}

			throw $e;
			
		}

	}

	/**
	 * Gibt den Namen für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Moodle: Synchronize student', 'TS » Apps » Moodle');
	}

}
