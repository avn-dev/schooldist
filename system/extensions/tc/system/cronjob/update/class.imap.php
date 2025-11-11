<?php

class Ext_TC_System_CronJob_Update_Imap extends Ext_TC_System_CronJob_Update {

	public $bIgnoreExecutionError = true;

	/**
	 * gibt den Klassennamen zurück
	 */
	public function getClassName() {
		return get_class($this);
	}

	protected function getUpdateData(){
		$aPostData = array();

		return $aPostData;
	}

	public function executeUpdate(){

		$oLog = Log::getLogger('imap');

		$cacheKey = __METHOD__.'_lock';
		$lock = \WDCache::get($cacheKey);

		if($lock == 1) {
			$oLog->addInfo('Already running');
			return;
		}
		
		\WDCache::set($cacheKey, 60*60*4, 1);

		// Ersten Aufruf merken, da erst ab jetzt bei versendeten E-Mail die message_id gespeichert wird.
		if(empty(\System::d('core_sync_sent_emails_start'))) {
			$syncStart = \Carbon\Carbon::tomorrow();
			\System::s('core_sync_sent_emails_start', $syncStart->toDateString());
		}
		
		try {
			
			// Imap Konten ermitteln
			$oAccountsCollection = Ext_TC_Communication_Imap::getAccounts();

			$oLog->addInfo('Start cronjob', array('accounts'=> $oAccountsCollection->count()));

			$start = microtime(true);

			// Konten durchlaufen und E-Mails abrufen
			foreach($oAccountsCollection as $oAccount) {

				\Core\Entity\ParallelProcessing\Stack::getRepository()
					->writeToStack('communication/imap-sync', ['account_id' => $oAccount->id], 10);

			}

			$end = microtime(true);

			$oLog->addInfo('End cronjob', ['duration' => $end - $start]);

		} catch (Exception $e) {

			__pout($e->getMessage());
			
			$oLog->addError('Exception', [$e]);
			
			Ext_TC_Util::reportError('TC Cronjob - Fehler - IMAP', $e);
		}

		\WDCache::delete($cacheKey);
		
	}

}