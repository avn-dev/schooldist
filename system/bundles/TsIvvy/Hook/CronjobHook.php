<?php

namespace TsIvvy\Hook;

use Core\Service\Hook\AbstractHook;
use TsIvvy\Api;
use TsIvvy\Handler\ExternalApp;
use TsIvvy\Service\Synchronization;

/**
 * Übernimmt Buchungen aus Ivvy
 *
 * Class CronjobHook
 * @package TsMews\Hook
 */
class CronjobHook extends AbstractHook {

	public function run() {

		if(!ExternalApp::isActive()) {
			return;
		}

		$lastSync = \System::d('ivvy_last_sync', null);

		// Letzte Änderungen seit der letzten Abfrage (-5 Minutes)
		$modifiedAfter = ($lastSync)
			? (new \DateTime())->setTimestamp($lastSync)->modify('-5 minutes')
			: null;

		Api::getLogger()->info('Execute cronjob', ['modified_after' => ($modifiedAfter) ? $modifiedAfter->format('Y-m-d H:i:s') : null]);

		try {

			$started = time();

			[$synced, $failed] = Synchronization::syncFromIvvy($modifiedAfter);

			\System::s('ivvy_last_sync', $started);

			Api::getLogger()->info('Cronjob finished', ['synced' => (int)$synced, 'failed' => (int)$failed]);

		} catch (\Throwable $ex) {
			Api::getLogger()->error('Cronjob failed', ['exception' => $ex->getMessage(), 'file' => $ex->getFile(), 'line' => $ex->getLine()]);
		}

	}

}
