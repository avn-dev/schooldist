<?php

class Ext_TS_System_Checks_Accommodation_ProviderPaymentsToAllocations extends GlobalChecks {
	
	public function getTitle() {
		return 'Update accommodation provider payment data';
	}
	
	public function getDescription() {
		return 'Adds allocation data to payments.';
	}
	
	/**
	 * Die aktuellen Bezahlungen werden ergänzt um zwei Spalten
	 * 
	 * @return boolean
	 */
	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '1G');

		$aBackupTables = array(
			'kolumbus_accommodations_payments'
		);
		
		foreach($aBackupTables as $sBackupTable) {
			$bBackup = Util::backupTable($sBackupTable);
			if(!$bBackup) {
				return false;
			}
		}

		DB::addField('kolumbus_accommodations_payments', 'until', 'DATE NULL', false, 'INDEX');
		DB::addField('kolumbus_accommodations_payments', 'allocation_id', 'INT NULL', false, 'INDEX');

		$sSql = "UPDATE `kolumbus_accommodations_payments` SET `until` = null WHERE `until` = '0000-00-00'";
		DB::executeQuery($sSql);

		$sCacheKeyEntity = 'wdbasic_table_description_kolumbus_accommodations_payments';
		WDCache::delete($sCacheKeyEntity);

		$sSql = "
			SELECT 
				*
			FROM
				`kolumbus_accommodations_payments`
			WHERE
				(
					`until` IS NULL OR
					`allocation_id` IS NULL
				) AND
				`inquiry_accommodation_id` > 0 AND
				`timepoint` != '0000-00-00' AND
				`active` = 1
			";
		
		$oDb = DB::getDefaultConnection();
		
		$aPayments = $oDb->getCollection($sSql);

		$iCounter = 1;
		foreach($aPayments as $aPayment) {

			$oPayment = Ext_Thebing_Accommodation_Payment::getInstance($aPayment['id']);
			
			$aUpdate = array();
			
			// Wert nicht überschreiben
			if(empty($aPayment['until'])) {

				$oUntil = $oPayment->getUntilDate();

				if($oUntil instanceof WDDate) {
					$aUpdate['until'] = $oUntil->get(WDDate::DB_DATE);
				}

			}

			if(empty($aPayment['allocation_id'])) {

				/*
				 * Hier ist eine Toleranz von sechs Tagen eingebaut. timepoint kann vom tatsächlichen Start abweichen.
				 * Ich denke das ging früher immer auf den Unterkunftsstarttag zurück
				 */
				$sSql = "
					SELECT
						*
					FROM 
						`kolumbus_accommodations_allocations`
					WHERE
						`inquiry_accommodation_id` = :inquiry_accommodation_id AND
						`room_id` = :room_id AND
						:timepoint BETWEEN DATE_SUB(`from`, INTERVAL 6 DAY) AND DATE_ADD(`until`, INTERVAL 6 DAY) AND
						`status` = 0 AND
						`active` = 1
				";
				$aSql = array(
					'inquiry_accommodation_id' => $aPayment['inquiry_accommodation_id'],
					'room_id' => $aPayment['room_id'],
					'timepoint' => $aPayment['timepoint']
				);

				$aAllocations = DB::getQueryRows($sSql, $aSql);

				// Wert nicht überschreiben
				if(count($aAllocations) > 0) {

					$aAllocation = reset($aAllocations);
					$aUpdate['allocation_id'] = $aAllocation['id'];

					if(count($aAllocations) > 1) {
						// Wenn mehr als eine Zuweisung gefunden wurde, sind die Bedingungen nicht eindeutig genug
						__pout($aPayment);
						__pout($aAllocations);
						// Keine Exception, da die älteren Daten durchaus fehlerhaft sein können!
						//throw new RuntimeException('More than one matching allocation found!');
					}

				}
			}

			if(!empty($aUpdate)) {
				DB::updateData('kolumbus_accommodations_payments', $aUpdate, '`id` = '.(int)$aPayment['id']);
			}
			
			if($iCounter % 100 == 0) {
				WDBasic::clearAllInstances();
			}

			$iCounter++;

		}

		return true;		
	}
	
}