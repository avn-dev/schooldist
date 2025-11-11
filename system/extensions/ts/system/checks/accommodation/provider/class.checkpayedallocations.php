<?php

class Ext_TS_System_Checks_Accommodation_Provider_CheckPayedAllocations extends GlobalChecks {

	public $aReport = array();

	public $bBackup = true;

	public function getTitle() {
		return 'Check payed allocations';
	}

	public function getDescription() {
		return 'Checks if an as paid marked allocation is completely payed.';
	}

	/**
	 * @return boolean
	 */
	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '1G');

		if($this->bBackup) {
			$bBackup = Util::backupTable('kolumbus_accommodations_allocations');
			if(!$bBackup) {
				return false;
			}
		}

		$sSql = "
			SELECT
				`kaa`.`id`,
				`kaa`.`until`,
				MAX(`kap`.`until`) `saved_until`,
				MAX(`ts_app`.`until`) `stack_until`
			FROM
				`kolumbus_accommodations_allocations` `kaa` LEFT JOIN
				`kolumbus_accommodations_payments` `kap` ON
					`kaa`.`id` = `kap`.`allocation_id` AND
					`kap`.`active` = 1 LEFT JOIN
				`ts_accommodation_providers_payments` `ts_app` ON
					`kaa`.`id` = `ts_app`.`accommodation_allocation_id`
			WHERE
				`kaa`.`active` = 1 AND
				`kaa`.`payment_generation_completed` IS NOT NULL
			GROUP BY
				`kaa`.`id`				
		";
		
		$aItems = DB::getQueryRows($sSql);
	
		$this->aReport = array(
			'entries_found' => count($aItems),
			'updated' => 0,
			'allocations' => array()
		);

		foreach($aItems as $aItem) {
			
			$dUntil = new DateTime($aItem['until']);
			$dUntilSavedUntil = null;
			$dUntilStackUntil = null;
			if(!empty($aItem['saved_until'])) {
				$dUntilSavedUntil = new DateTime($aItem['saved_until']);
			}
			if(!empty($aItem['stack_until'])) {
				$dUntilStackUntil = new DateTime($aItem['stack_until']);
			}
			
			$dUntilMax = max($dUntilSavedUntil, $dUntilStackUntil);

			// Wenn die Zuweisung länger dauert
			if($dUntil > $dUntilMax) {

				$aUpdate = array(
					'payment_generation_completed' => null
				);
				DB::updateData('kolumbus_accommodations_allocations', $aUpdate, '`id` = '.(int)$aItem['id']);

				$this->aReport['allocations'][] = $aItem;
				
				$this->aReport['updated']++;

			}
			
		}

		$this->logInfo('Check ready', $this->aReport);
		
		return true;
	}

}