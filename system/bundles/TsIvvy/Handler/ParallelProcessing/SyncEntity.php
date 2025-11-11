<?php

namespace TsIvvy\Handler\ParallelProcessing;

use Core\Enums\AlertLevel;
use Core\Handler\ParallelProcessing\TypeHandler;
use TsIvvy\Handler\ExternalApp;
use TsIvvy\Service\Synchronization;

class SyncEntity extends TypeHandler {

	/**
	 * Gibt den Namen für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Ivvy: Entität synchronisieren', 'TS » Apps » Ivvy');
	}

	/**
	 * @inheritdoc
	 */
	public function execute(array $data, $debug = false) {

		if(ExternalApp::isActive()) {

			$entity = \Factory::getInstance($data['entity'], $data['entity_id']);

			try {

				Synchronization::syncEntityToIvvy($entity);

			} catch(\Throwable $e) {

				if (isset($data['user_id'])) {
					$user = \User::getInstance($data['user_id']);
					// Über "Offene Hintergrundaufgaben" macht es keinen Sinn nochmal eine Notification zu senden da man
					// dort das Feedback direkt sieht
					if(php_sapi_name() == 'cli' && $user->exist()) {
						\Core\Service\NotificationService::sendToUser($user, sprintf(\L10N::t('Entity "%s(%s)" could not be synchronized with Ivvy!', 'TS » Apps » Ivvy'), $data['entity'], $data['entity_id']), AlertLevel::DANGER);
					}
				}

				throw $e;

			}
		}

	}

}
