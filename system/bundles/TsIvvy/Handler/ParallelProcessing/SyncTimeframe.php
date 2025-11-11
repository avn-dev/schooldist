<?php

namespace TsIvvy\Handler\ParallelProcessing;

use TsIvvy\Api;
use TsIvvy\Handler\ExternalApp;
use TsIvvy\Service\Synchronization;
use Core\Handler\ParallelProcessing\TypeHandler;

class SyncTimeframe extends TypeHandler
{
	public function getLabel()
	{
		return \L10N::t('Ivvy: Zeitraum synchronisieren', 'TS » Apps » Ivvy');
	}

	public function execute(array $data, $debug = false)
	{
		if (!ExternalApp::isActive()) {
			return true;
		}

		$from = new \DateTime($data['start'], new \DateTimeZone('UTC'));
		$end = (new \DateTime($data['end'], new \DateTimeZone('UTC')))->setTime(23, 59, 59);

		[$synced, $failed] = Synchronization::syncFromIvvy($from, $end);

		Api::getLogger()->info('PP: Sync', ['start' => $from->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s'), 'synced' => $synced, 'failed' => $failed]);

		if (!empty($data['user_id'])) {
			$user = \User::getInstance($data['user_id']);
			// Über "Offene Hintergrundaufgaben" macht es keinen Sinn nochmal eine Notification zu senden da man
			// dort das Feedback direkt sieht
			if(php_sapi_name() === 'cli' && $user->exist()) {
				\Core\Service\NotificationService::sendToUser($user, \L10N::t('Die Ivvy-Synchronisation ist abgeschlossen.', 'TS » Apps » Ivvy'), 'info');
			}
		}

		return true;
	}

}
