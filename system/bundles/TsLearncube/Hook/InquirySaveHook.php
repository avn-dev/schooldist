<?php

namespace TsLearncube\Hook;

class InquirySaveHook extends \Core\Service\Hook\AbstractHook {

	public function run(\Ext_TS_Inquiry $inquiry) {

		$school = $inquiry->getSchool();
		$learncubeUrl = \System::d(\TsLearncube\Handler\ExternalApp::KEY_URL.'_'.$school->id);

		if(
			!empty($learncubeUrl) &&
			\TcExternalApps\Service\AppService::hasApp(\TsLearncube\Handler\ExternalApp::APP_NAME)
		) {

			// TODO @MP Warum? Es gibt eine Aktion in den Tools dafür.
//			// Bei Debugmodus an wird der Abgleich direkt vorgenommen, damit man besser entwickeln kann
//			if(\System::d('debugmode')) {
//
//				$learncubeService = new \TsLearncube\Service\LearncubeWebService\Inquiry($school);
//				$learncubeService->sync($inquiry);
//			} else {
				$oUser = \Access::getInstance();

				$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
				$oStackRepository->writeToStack('ts-learncube/sync-inquiry', ['inquiry_id' => $inquiry->id, 'user_id' => $oUser?->id], 2);
//			}
		}
	}
}