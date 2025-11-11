<?php

namespace TsMoodle\Hook;

class ClassSaveHook extends \Core\Service\Hook\AbstractHook {
	
	public function run(\Ext_Thebing_Tuition_Class $class) {

		$school = $class->getSchool();
		$moodleUrl = \System::d(\TsMoodle\Handler\ExternalApp::KEY_URL.'_'.$school->id);
		
		if(
			!empty($moodleUrl) &&
			\TcExternalApps\Service\AppService::hasApp(\TsMoodle\Handler\ExternalApp::APP_NAME)
		) {

			// Bei Debugmodus an wird der Abgleich direkt vorgenommen, damit man besser entwickeln kann
			if(\System::d('debugmode')) {
				
				$oMoodleService = new \TsMoodle\Service\MoodleWebService\TuitionClass($school);
				$oMoodleService->sync($class);
				
			} else {

				$oUser = \Access::getInstance();

				$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
				$oStackRepository->writeToStack('ts-moodle/sync-class', ['class_id' => $class->id, 'user_id' => $oUser->id], 2);

			}

		}

	}

}
