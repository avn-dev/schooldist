<?php

namespace TsMoodle\Hook;

class InquirySaveHook extends \Core\Service\Hook\AbstractHook {
	
	public function run(\Ext_TS_Inquiry $oInquiry) {

		$school = $oInquiry->getSchool();
		$moodleUrl = \System::d(\TsMoodle\Handler\ExternalApp::KEY_URL.'_'.$school->id);
		
		if(
			!empty($moodleUrl) &&
			\TcExternalApps\Service\AppService::hasApp(\TsMoodle\Handler\ExternalApp::APP_NAME)
		) {

			// Bei Debugmodus an wird der Abgleich direkt vorgenommen, damit man besser entwickeln kann
			// Lösung: PP aus und über Tools ausführen. Auf Pre-Live kommt man damit nicht klar, da nicht jeder Schüler eine E-Mail hat.
//			if(\System::d('debugmode')) {
//
//				$oMoodleService = new \TsMoodle\Service\MoodleWebService\Student($school);
//				$oMoodleService->sync($oInquiry);
//
//			} else {

				$oUser = \Access::getInstance();

				$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
				$oStackRepository->writeToStack('ts-moodle/sync-inquiry', ['inquiry_id' => $oInquiry->id, 'user_id' => $oUser->id], 2);

//			}

		}

	}
	
}