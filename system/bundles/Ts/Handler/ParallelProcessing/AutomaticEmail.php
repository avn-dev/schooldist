<?php

namespace Ts\Handler\ParallelProcessing;

use Communication\Exceptions\Mail\AccountConnectionLocked;
use Communication\Services\ConnectionLock;
use Core\Exception\ParallelProcessing\RewriteException;
use Core\Handler\ParallelProcessing\TypeHandler;

class AutomaticEmail extends TypeHandler {

	/**
	 * @inheritdoc
	 */
	public function getLabel() {
		return \L10N::t('E-Mail-Versand');
	}

	/**
	 * Versand zweimal versuchen
	 * @return int
	 */
	public function getRewriteAttempts() {
		return 2;
	}

	/**
	 * @inheritdoc
	 */
	public function execute(array $aData, $bDebug = false) {

		try {

			$bSuccess = \Ext_Thebing_Mail::sendAutoMail($aData, 'cronjob');

			if(!$bSuccess) {
				throw new \RuntimeException(sprintf('TS AutomaticEmail failed! %s (%s::%d)', \Ext_TC_Communication_WDMail::$lastError, $aData['object'], $aData['object_id']));
			}

		} catch (\Throwable $e) {

			$this->tryRewrite($e);

			throw $e;
		}

		return $bSuccess;
	}

	private function tryRewrite(\Throwable $e): void
	{
		if (
			$e instanceof AccountConnectionLocked ||
			// Falls doch mal ein Microsoft-Fehler durchkommt
			str_contains($e->getMessage(), 'connections limit exceeded')
		) {
			sleep(ConnectionLock::LOCK_DURATION * 0.5);
			// Zu viele Verbindungen, den Task erneut in den Stack schreiben
			throw new RewriteException($e->getMessage());
		}
	}
}
