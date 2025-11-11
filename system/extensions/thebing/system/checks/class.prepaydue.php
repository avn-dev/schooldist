<?php

/**
 * Es werden die vergessenen Prepay
 */
class Ext_Thebing_System_Checks_Prepaydue extends GlobalChecks
{

	public function getTitle()
	{
		$sTitle = 'Prepay Due';
		return $sTitle;
	}

	public function getDescription()
	{
		$sDescription = 'Prepay Due caching';
		return $sDescription;
	}
 
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		
		$oDB = DB::getDefaultConnection();

			
		// amount_finalpay_due füllen
		$sSql = "SELECT
						`ts_i`.`id` `id`,
						MIN(`kidv`.`amount_finalpay_due`) `amount_finalpay_due`
					FROM
						`ts_inquiries` `ts_i` INNER JOIN
						`kolumbus_inquiries_documents` `kid` INNER JOIN
						`kolumbus_inquiries_documents_versions` `kidv` ON
							`kid`.`id` = `kidv`.`document_id` AND
							`kidv`.`active` = 1 AND
							`kidv`.`id` = `kid`.`latest_version`
					WHERE
						`kid`.`inquiry_id` = `ts_i`.`id` AND
						`kid`.`active` = 1 AND
						`kidv`.`amount_finalpay_due` > 0 AND
						`kid`.`type` IN (
											'brutto',
											'netto',
											'brutto_diff',
											'brutto_diff_special',
											'netto_diff',
											'credit_brutto',
											'credit_netto'
											)
					GROUP BY `ts_i`.`id`
				";


		$aData = $oDB->getCollection($sSql, array());	
		foreach($aData as $aResult){
			$sSql = "UPDATE
							`ts_inquiries`
						SET
							`amount_finalpay_due` = :date
						WHERE
							`id` = :id
			";
			$aSql = array();
			$aSql['id'] = (int)$aResult['id'];
			$aSql['date'] = $aResult['amount_finalpay_due'];

			DB::executePreparedQuery($sSql, $aSql);
		}

		// amount_prepay_due füllen
		$sSql = "SELECT
						`ts_i`.`id` `id`,
						MIN(`kidv`.`amount_prepay_due`) `amount_prepay_due`
					FROM
						`ts_inquiries` `ts_i` INNER JOIN
						`kolumbus_inquiries_documents` `kid` INNER JOIN
						`kolumbus_inquiries_documents_versions` `kidv` ON
							`kid`.`id` = `kidv`.`document_id` AND
							`kidv`.`active` = 1 AND
							`kidv`.`id` = `kid`.`latest_version`
					WHERE
						`kid`.`inquiry_id` = `ts_i`.`id` AND
						`kid`.`active` = 1 AND
						`kidv`.`amount_prepay_due` > 0 AND
						`kid`.`type` IN (
											'brutto',
											'netto',
											'brutto_diff',
											'brutto_diff_special',
											'netto_diff',
											'credit_brutto',
											'credit_netto'
											)
					GROUP BY `ts_i`.`id`
				";
		$aData = $oDB->getCollection($sSql, array());


		foreach($aData as $aResult){
			$sSql = "UPDATE
							`ts_inquiries`
						SET
							`amount_prepay_due` = :date
						WHERE
							`id` = :id
			";
			$aSql = array();
			$aSql['id'] = (int)$aResult['id'];
			$aSql['date'] = $aResult['amount_prepay_due'];

			DB::executePreparedQuery($sSql, $aSql);
		}



		//amount_prepay füllen
		$sSql = "SELECT
						`ts_i`.`id` `id`,
						SUM(`kidv`.`amount_prepay`) `amount_prepay`
					FROM
						`ts_inquiries` `ts_i` INNER JOIN
						`kolumbus_inquiries_documents` `kid` INNER JOIN
						`kolumbus_inquiries_documents_versions` `kidv` ON
							`kid`.`id` = `kidv`.`document_id` AND
							`kidv`.`active` = 1 AND
							`kidv`.`id` = `kid`.`latest_version`
					WHERE
						`kid`.`inquiry_id` = `ts_i`.`id` AND
						`kid`.`active` = 1 AND
						`kidv`.`amount_prepay` > 0 AND
						`kid`.`type` IN (
											'brutto',
											'netto',
											'brutto_diff',
											'brutto_diff_special',
											'netto_diff',
											'credit_brutto',
											'credit_netto'
											)
					GROUP BY `ts_i`.`id`
				";
		$aData = $oDB->getCollection($sSql, array());

		foreach($aData as $aResult){
			$sSql = "UPDATE
							`ts_inquiries`
						SET
							`amount_prepay` = :date
						WHERE
							`id` = :id
			";
			$aSql = array();
			$aSql['id'] = (int)$aResult['id'];
			$aSql['date'] = $aResult['amount_prepay'];

			DB::executePreparedQuery($sSql, $aSql);
		}

		
		
		return true;
	}
}
