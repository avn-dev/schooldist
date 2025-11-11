<?php

namespace TsLearncube\Handler\ParallelProccessing;

use Core\Enums\AlertLevel;
use Core\Handler\ParallelProcessing\TypeHandler;
use TsLearncube\Handler\ExternalApp;

class SyncInquiry extends TypeHandler
{

	/**
	 * @inheritdoc
	 */
	public function execute(array $aData, $bDebug = false)
	{

		$inquiry = \Ext_TS_Inquiry::getInstance($aData['inquiry_id']);
		$user = \User::getInstance($aData['user_id']);

		$log = \Log::getLogger('api', 'learncube');

		try {

			$learncubeService = new \TsLearncube\Service\LearncubeWebService\Inquiry($inquiry->getSchool());
			$success = $learncubeService->sync($inquiry);

			if (
				$user->exist() &&
				$success
			) {
				\Core\Service\NotificationService::sendToUser($user, sprintf(\L10N::t('Booking "%s" from student "%s" has been successfully synchronized with Learncube!', ExternalApp::L10N_PATH), $inquiry->getNumber(), $inquiry->getFirstTraveller()->getCustomerNumber()), AlertLevel::SUCCESS);
				$log->info('Success', $aData);
			}

		} catch (\Throwable $e) {

			$aInfo = [$aData, $e];

			$log->error('Failure', $aInfo);

			if ($user->exist()) {
				\Core\Service\NotificationService::sendToUser($user, sprintf(\L10N::t('Booking "%s" from student "%s" could not be synchronized with Learncube (%s)!', ExternalApp::L10N_PATH), $inquiry->getNumber(), $inquiry->getFirstTraveller()->getCustomerNumber(), $e->getMessage()), AlertLevel::DANGER);
			}

			throw $e;

		}

	}

	/**
	 * Gibt den Namen für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel()
	{
		return \L10N::t('Learncube: Synchronize student', ExternalApp::L10N_PATH);
	}

}
