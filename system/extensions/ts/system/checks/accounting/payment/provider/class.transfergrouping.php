<?php

/**
 * https://redmine.thebing.com/redmine/issues/5115
 */
class Ext_TS_System_Checks_Accounting_Payment_Provider_TransferGrouping extends GlobalChecks {

	public function getTitle() {
		return 'Update of transfer payment groupings';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`ts_tpg`.`id`,
				`ts_ijt`.`provider_type`,
				`ts_ij`.`school_id`
			FROM
				`ts_transfers_payments_groupings` `ts_tpg` LEFT JOIN
				`kolumbus_transfers_payments` `ktrp` ON
					`ktrp`.`grouping_id` = `ts_tpg`.`id` LEFT JOIN
				`ts_inquiries_journeys_transfers` `ts_ijt` ON
					`ts_ijt`.`id` = `ktrp`.`inquiry_transfer_id` LEFT JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_ijt`.`journey_id`
			GROUP BY
				`ts_tpg`.`id`
		";

		$aResult = (array)DB::getQueryRows($sSql);

		foreach($aResult as $aData) {

			if(empty($aData['provider_type'])) {
				$this->logError('No provider type found for transfer payment grouping '.$aData['id']);
				$aData['provider_type'] = 'provider';
			}

			// Kaputte Zahlung
			if(empty($aData['school_id'])) {
				$this->logError('No school found for transfer payment grouping'.$aData['id']);
			}

			$aUpdateData = [
				'school_id' => (int)$aData['school_id'],
				'provider_type' => $aData['provider_type']
			];

			DB::updateData('ts_transfers_payments_groupings', $aUpdateData, '`id`='.$aData['id']);

		}

		DB::commit(__CLASS__);

		return true;
	}
}