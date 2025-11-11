<?php

namespace TsMoodle\Hook;

class CourseSaveHook extends \Core\Service\Hook\AbstractHook {
	
	public function run(\Ext_Thebing_Tuition_Course $course) {

		$school = $course->getSchool();
		$moodleUrl = \System::d(\TsMoodle\Handler\ExternalApp::KEY_URL.'_'.$school->id);
		
		if(
			!empty($moodleUrl) &&
			\TcExternalApps\Service\AppService::hasApp(\TsMoodle\Handler\ExternalApp::APP_NAME)
		) {

			// Bei Debugmodus an wird der Abgleich direkt vorgenommen, damit man besser entwickeln kann
			if(\System::d('debugmode')) {
				
				$oMoodleService = new \TsMoodle\Service\MoodleWebService\Course($school);
				$oMoodleService->sync($course);
				
			} else {

				$oUser = \Access::getInstance();

				$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
				$oStackRepository->writeToStack('ts-moodle/sync-course', ['course_id' => $course->id, 'user_id' => $oUser->id], 2);

			}

		}

	}
}
