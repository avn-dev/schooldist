<?php

namespace TsHubspot\Handler\ParallelProcessing;

use Core\Enums\AlertLevel;
use Core\Exception\ParallelProcessing\RewriteException;
use Core\Facade\Cache;
use Core\Handler\ParallelProcessing\TypeHandler;
use TsHubspot\Factory\ObjectFactory;
use TsHubspot\Service\Helper\General;
use TsHubspot\Service\Inquiry;

class Transfer extends TypeHandler {

	public static $user;

	const PROCESS_LOCKED_CACHE_KEY = 'hubspot_process_locked';

	/**
	 * @inheritdoc
	 */
	public function execute(array $aData, $bDebug = false) {

		if ($this->isProcessLocked()) {
			throw new RewriteException('Process is locked');
		}

		try {
			General::checkHubspotAPILimit();
		} catch (RewriteException $e) {
			// Hubspot API Limit -> Rewrite Exception also nochmal den Task ausführen
			// -> Das Limit ist für 10 Sekunden, also sollte dann das Limit wieder zurückgesetzt sein
			// Lock aufheben
			Cache::forget(self::PROCESS_LOCKED_CACHE_KEY);
			throw $e;
		}

		// Statische Variable, damit man später mit dem User weiterarbeiten kann (::sendToUser) und wegen des PPs funktioniert
		// \Access_Backend::getInstance()->getUser() nicht
		self::$user = \User::getInstance($aData['user_id']);

		$entity = $aData['entity_classname']::getInstance($aData['entity_id']);

		$objFactory = new ObjectFactory($entity);
		$serviceClassname = $objFactory->getService();

		$success = false;
		try {
			$service = new $serviceClassname($entity);

			// Bei Hubspot-Kontaktsuche (Bei Buchung)
			if (!empty($aData['traveller_hubspot_id'])) {
				Inquiry::$travellerHubspotId = $aData['traveller_hubspot_id'];
			}
			$success = $service->update();
		} catch (\Exception $e) {
			$errorMessage = $e->getMessage();
		}

		if (self::$user->exist()) {
			if ($success) {
				\Core\Service\NotificationService::sendToUser(self::$user, sprintf(\L10N::t('"%s" wurde Erfolgreich mit Hubspot synchronisiert!'), $service->getResponseMessage()), AlertLevel::SUCCESS);
			} else {
				\Core\Service\NotificationService::sendToUser(self::$user, sprintf(\L10N::t('Beim Synchronisieren mit Hubspot ist ein Fehler aufgetreten: "%s"'), $errorMessage), AlertLevel::DANGER);
			}
		}

		// Lock aufheben
		Cache::forget(self::PROCESS_LOCKED_CACHE_KEY);
	}

	/**
	 * Gibt den Namen für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel() {
		return \L10N::t('Hubspot-Synchronisierung', 'School');
	}

	public function getRewriteAttempts() {
		return 30;
	}

	public function isProcessLocked() {
		for ($i = 0; $i < 10; $i++) {
			$status = Cache::put(self::PROCESS_LOCKED_CACHE_KEY, 30, 1);
			if ($status === \Core\Service\Cache::STATUS_ADDED) {
				return false;
			}
			sleep(1);
		}

		return true;
	}

}