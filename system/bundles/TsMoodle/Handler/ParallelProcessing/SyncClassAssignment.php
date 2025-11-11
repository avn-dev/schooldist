<?php

namespace TsMoodle\Handler\ParallelProcessing;

use Core\Enums\AlertLevel;
use Core\Handler\ParallelProcessing\TypeHandler;

/**
 * Class AgencyTransfer Parallelprocessing - Hubspot Transfer-Handler für Agenturen und Agenturmitarbeiter
 * @package TsHubspot\Handler
 */
class SyncClassAssignment extends TypeHandler {

	/**
	 * @inheritdoc
	 */
	public function execute(array $aData, $bDebug = false) {

		$class = \Ext_Thebing_Tuition_Class::getInstance($aData['class_id']);
		$course = \Ext_Thebing_Tuition_Course::getInstance($aData['course_id']);
		$journeyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($aData['journey_course_id']);
		$oUser = \User::getInstance($aData['user_id']);

		$oLog = \Log::getLogger('api', 'moodle');
		
		try {
			
			$oMoodleService = new \TsMoodle\Service\MoodleWebService\TuitionClassAssignment($journeyCourse->getSchool());
			$oMoodleService->sync($class, $journeyCourse, $course);

			if($oUser->exist()) {
				\Core\Service\NotificationService::sendToUser($oUser, sprintf(\L10N::t('Assignment of student "%s" has been successfully synchronized with Moodle!', 'TS » Apps » Moodle'), $journeyCourse->getInquiry()->getFirstTraveller()->getName()), AlertLevel::SUCCESS);
			}
			
		} catch(\Exception | \Error $e) {

			$aInfo = [$aData, $e];
			if($e instanceof \MoodleSDK\Util\MoodleException) {
				$aInfo[] = $e->getMethod();
				$aInfo[] = $e->getPayload();
			}
			$oLog->error('Failure', $aInfo);
			
			if($oUser->exist()) {
				\Core\Service\NotificationService::sendToUser($oUser, sprintf(\L10N::t('Assignment of student "%s" could not be synchronized with Moodle (%s)!', 'TS » Apps » Moodle'), $journeyCourse->getInquiry()->getFirstTraveller()->getName(), $e->getMessage()), AlertLevel::DANGER);
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
		return \L10N::t('Moodle: Synchronize booking', 'TS » Apps » Moodle');
	}

}
