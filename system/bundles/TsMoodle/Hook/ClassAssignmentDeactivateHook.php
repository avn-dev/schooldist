<?php

namespace TsMoodle\Hook;

class ClassAssignmentDeactivateHook extends \Core\Service\Hook\AbstractHook {
	
	public function run(\Ext_Thebing_School_Tuition_Block $block, array $blockIds, int $inquiryCourseId) {

		$school = $block->getSchool();
		$moodleUrl = \System::d(\TsMoodle\Handler\ExternalApp::KEY_URL.'_'.$school->id);

		if(
			!empty($moodleUrl) &&
			\TcExternalApps\Service\AppService::hasApp(\TsMoodle\Handler\ExternalApp::APP_NAME)
		) {

			$class = $block->getClass();
			$journeyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($inquiryCourseId);
			
			// Bei Debugmodus an wird der Abgleich direkt vorgenommen, damit man besser entwickeln kann
			if(\System::d('debugmode')) {
				
				$oMoodleService = new \TsMoodle\Service\MoodleWebService\TuitionClassAssignment($school);
				$oMoodleService->sync($class, $journeyCourse, null);
				
			} else {

				$oUser = \Access::getInstance();

				$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
				$oStackRepository->writeToStack('ts-moodle/sync-class-assignment', ['class_id' => $class->id, 'course_id'=>null, 'journey_course_id'=>$journeyCourse->id, 'user_id' => $oUser->id], 2);

			}

		}

	}
	
}
