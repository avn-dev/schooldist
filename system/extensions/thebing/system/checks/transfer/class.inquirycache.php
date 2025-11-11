<?php

class Ext_Thebing_System_Checks_Transfer_InquiryCache extends GlobalChecks {

	protected $_aErrors = array();

	public function getTitle() {
		$sTitle = 'Update inquiry cache';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Update transfer data in inquiry cache.';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Util::backupTable('ts_inquiries');

		// TransferDaten holen (Anreise)
		$sSql = "SELECT
						`ts_i_j_t`.`transfer_date` `transfer_date`,
						`ts_i`.`id` `id`
					FROM
						`ts_inquiries_journeys_transfers` `ts_i_j_t` INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`id` = `ts_i_j_t`.`journey_id` AND
							`ts_i_j`.`active` = 1 INNER JOIN
						`ts_inquiries` `ts_i` ON
							`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
							`ts_i`.`active` = 1 AND
							`ts_i`.`tsp_transfer` IN ('arrival', 'arr_dep')
					WHERE
						`ts_i_j_t`.`active` = 1 AND
						`ts_i_j_t`.`transfer_type` = 1
				"; 
		
		$aData = DB::getQueryData($sSql);

		// Löschen
		$sSql = "
			UPDATE
				`ts_inquiries`
			SET
				`arrival_date` = '0000-00-00'
			";
		DB::executeQuery($sSql);
		
		foreach($aData as $aResult){
			$sSql = "UPDATE
							`ts_inquiries`
						SET
							`arrival_date` = :date
						WHERE
							`id` = :id
			";
			$aSql = array();
			$aSql['id'] = (int)$aResult['id'];
			$aSql['date'] = (string)$aResult['transfer_date'];

			DB::executePreparedQuery($sSql, $aSql);
		}
		
		// TransferDaten holen (Abreise)
		$sSql = "SELECT
						`ts_i_j_t`.`transfer_date` `transfer_date`,
						`ts_i`.`id` `id`
					FROM
						`ts_inquiries_journeys_transfers` `ts_i_j_t` INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`id` = `ts_i_j_t`.`journey_id` AND
							`ts_i_j`.`active` = 1 INNER JOIN
						`ts_inquiries` `ts_i` ON
							`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
							`ts_i`.`active` = 1 AND
							`ts_i`.`tsp_transfer` IN ('departure', 'arr_dep')
					WHERE
						`ts_i_j_t`.`active` = 1 AND
						`ts_i_j_t`.`transfer_type` = 2
				"; 
		
		$aData = DB::getQueryData($sSql);

		// Löschen
		$sSql = "
			UPDATE
				`ts_inquiries`
			SET
				`departure_date` = '0000-00-00'
			";  
		DB::executeQuery($sSql);
		
		foreach($aData as $aResult){
			$sSql = "
				UPDATE
					`ts_inquiries`
				SET
					`departure_date` = :date
				WHERE
					`id` = :id
			";
			$aSql = array();
			$aSql['id'] = (int)$aResult['id'];
			$aSql['date'] = (string)$aResult['transfer_date'];

			DB::executePreparedQuery($sSql, $aSql);
		}

		return true;
			
	}
	
}
