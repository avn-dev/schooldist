<?php

namespace TsMoodle\Handler\ParallelProcessing;

use Core\Enums\AlertLevel;
use Core\Handler\ParallelProcessing\TypeHandler;

/**
 * Class AgencyTransfer Parallelprocessing - Hubspot Transfer-Handler für Agenturen und Agenturmitarbeiter
 * @package TsHubspot\Handler
 */
class SyncCourse extends TypeHandler {

	/**
	 * @inheritdoc
	 */
	public function execute(array $aData, $bDebug = false) {

		$course = \Ext_Thebing_Tuition_Course::getInstance($aData['course_id']);
		$oUser = \User::getInstance($aData['user_id']);
		
		$oLog = \Log::getLogger('api', 'moodle');
		
		try {
			
			$oMoodleService = new \TsMoodle\Service\MoodleWebService\Course($course->getSchool());
			$oMoodleService->sync($course);

			if($oUser->exist()) {
				\Core\Service\NotificationService::sendToUser($oUser, sprintf(\L10N::t('Course "%s" has been successfully synchronized with Moodle!', 'TS » Apps » Moodle'), $course->getName()), AlertLevel::SUCCESS);
			}
			
		} catch(\Exception | \Error $e) {

			$aInfo = [$aData, $e];
			if($e instanceof \MoodleSDK\Util\MoodleException) {
				$aInfo[] = $e->getMethod();
				$aInfo[] = $e->getPayload();
			}
			$oLog->error('Failure', $aInfo);
			
			if($oUser->exist()) {
				\Core\Service\NotificationService::sendToUser($oUser, sprintf(\L10N::t('Course "%s" could not be synchronized with Moodle (%s)!', 'TS » Apps » Moodle'), $course->getName(), $e->getMessage()), AlertLevel::DANGER);
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
		return \L10N::t('Moodle: Synchronize course', 'TS » Apps » Moodle');
	}

}
