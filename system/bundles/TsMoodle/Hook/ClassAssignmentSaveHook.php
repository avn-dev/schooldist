<?php

namespace TsMoodle\Hook;

class ClassAssignmentSaveHook extends \Core\Service\Hook\AbstractHook {
	
	public function run(\Ext_Thebing_School_Tuition_Allocation $assignment) {

		$school = $assignment->getJourneyCourse()->getSchool();
		$moodleUrl = \System::d(\TsMoodle\Handler\ExternalApp::KEY_URL.'_'.$school->id);

		if(
			!empty($moodleUrl) &&
			\TcExternalApps\Service\AppService::hasApp(\TsMoodle\Handler\ExternalApp::APP_NAME)
		) {
			
			$class = $assignment->getBlock()->getClass();
			$journeyCourse = $assignment->getJourneyCourse();
			$course = $assignment->getCourse();
			
			// Bei Debugmodus an wird der Abgleich direkt vorgenommen, damit man besser entwickeln kann
			if(\System::d('debugmode')) {
				
				$oMoodleService = new \TsMoodle\Service\MoodleWebService\TuitionClassAssignment($school);
				$oMoodleService->sync($class, $journeyCourse, $course);
				
			} else {

				$oUser = \Access::getInstance();

				$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
				$oStackRepository->writeToStack('ts-moodle/sync-class-assignment', ['class_id' => $class->id, 'course_id'=>$assignment->course_id, 'journey_course_id'=>$journeyCourse->id, 'user_id' => $oUser->id], 2);

			}

		}

	}
	
}
