<?php

class Ext_TS_System_Checks_Transfer_CheckTransferRecords extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Check transfer records';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Transfer records of groups are controlled';
		return $sDescription;
	}

	/**
	 * Löscht alle doppelten Transfer Einträge
	 * Für mehr Informationen siehe Ticket #5843
	 *
	 * @return boolean
	 */
	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '2048M');

		$bSuccess = Util::backupTable('ts_inquiries_journeys_transfers');
		if(!$bSuccess) {
			return false;
		}

		$sSql = "
			SELECT
				`id`, `active`, `transfer_type`, `journey_id`, `created`
			FROM
				`ts_inquiries_journeys_transfers`
			WHERE
				`active` = 1 AND
				`transfer_type` != 0
		";
		$aData = DB::getQueryData($sSql);

		$aFoundIds = array();
		foreach($aData as $aResult) {
			if(isset($aFoundIds[$aResult['id']])) {
				continue;
			}
			$sSql = "
				SELECT
					`tsijt`.`id`, `tsijt`.`active`, `tsijt`.`transfer_type`, `tsijt`.`journey_id`, `tsijt`.`created`, `tsij`.`inquiry_id`
				FROM
					`ts_inquiries_journeys_transfers` `tsijt` INNER JOIN
					`ts_inquiries_journeys` `tsij` ON
						`tsij`.`id` = `tsijt`.`journey_id`
				WHERE
					`tsijt`.`id` != :id AND
					`tsijt`.`active` = :active AND
					`tsijt`.`transfer_type`	= :transfer_type AND
					`tsijt`.`journey_id` = :journey_id
				LIMIT 1
			";
			$aSpecificResult = DB::getPreparedQueryData($sSql, $aResult);
			if(!empty($aSpecificResult)) {
				$aFoundIds[$aSpecificResult[0]['id']] = true;
				$oDateTimeData = new DateTime($aResult['created']);
				$oDateTimeOneData = new DateTime($aSpecificResult[0]['created']);
				if($oDateTimeData < $oDateTimeOneData) {
					$iDeleteId = $aResult['id'];
				} else {
					$iDeleteId = $aSpecificResult[0]['id'];
				}
				DB::updateData('ts_inquiries_journeys_transfers', array('active' => 0), ' `id` = ' . $iDeleteId);
				Ext_Gui2_Index_Stack::add('ts_inquiry', (int)$aSpecificResult[0]['inquiry_id'], 0);
				$this->logInfo('Set record with id ' . $iDeleteId . ' to active = 0');
			}
		}
		Ext_Gui2_Index_Stack::executeCache();

		return true;
	}
	
}
