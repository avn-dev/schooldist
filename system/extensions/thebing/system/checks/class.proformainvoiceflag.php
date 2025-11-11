<?php


class Ext_Thebing_System_Checks_ProformaInvoiceFlag extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		if(Util::checkTableExists('kolumbus_inquiries'))
		{
			$sTable = 'kolumbus_inquiries';
		}
		elseif(Util::checkTableExists('ts_inquiries'))
		{
			$sTable = 'ts_inquiries';
		}
		else
		{
			throw new Exception('inquiry table not found!');
		}
		

		Ext_Thebing_Util::backupTable($sTable);

		$aColumns = DB::describeTable($sTable);
		
		$iIssetCheck1 = 0;
		if(isset($aColumns['has_invoice']))
		{
			$iIssetCheck1 = 1;
		}
		
		$iIssetCheck2 = 0;
		if(isset($aColumns['has_proforma']))
		{
			$iIssetCheck2 = 1;
		}
		$iSumCheck = $iIssetCheck1 + $iIssetCheck2;
		
		if($iSumCheck==1){
			return true;
		}

		if($iSumCheck==0)
		{
			$sSql = "
				ALTER TABLE
					".$sTable." ADD `has_invoice` TINYINT( 1 ) NOT NULL ,
				ADD `has_proforma` TINYINT( 1 ) NOT NULL
			";

			DB::executeQuery($sSql);
		}

		$sSql = "
			SELECT
				`ki`.`id` `inquiry_id`,
				SUM(
					IF(
						`kid_invoice`.`id` IS NOT NULL,
						1,
						0
					)
				) `count_invoice`,
				SUM(
					IF(
						`kid_proforma`.`id` IS NOT NULL,
						1,
						0
					)
				) `count_proforma`
			FROM
				".$sTable." `ki` LEFT JOIN
				`kolumbus_inquiries_documents` `kid_invoice` ON
					`kid_invoice`.`inquiry_id` = `ki`.`id` AND
					`kid_invoice`.`active` = 1 AND
					`kid_invoice`.`type` IN('brutto','netto') LEFT JOIN
				`kolumbus_inquiries_documents` `kid_proforma` ON
					`kid_proforma`.`inquiry_id` = `ki`.`id` AND
					`kid_proforma`.`active` = 1 AND
					`kid_proforma`.`type` IN('proforma_brutto','proforma_netto')
			WHERE
				`ki`.`active` = 1 AND
				(
					`kid_invoice`.`id` IS NOT NULL OR
					`kid_proforma`.`id` IS NOT NULL
				)
			GROUP BY
				`ki`.`id`
		";

		$aResult = DB::getQueryRows($sSql);

		foreach($aResult as $aRowData)
		{
			$iInvoice = $aRowData['count_invoice'];
			if($iInvoice>1)
			{
				$iInvoice = 1;
			}
			$iProforma = $aRowData['count_proforma'];
			if($iProforma>1)
			{
				$iProforma = 1;
			}

			$sWhere = 'id = '.(int)$aRowData['inquiry_id'];

			DB::updateData($sTable, array(
				'has_invoice'	=> $iInvoice,
				'has_proforma'	=> $iProforma,
			), $sWhere);
		}

		return true;
	}

	public function getTitle()
	{
		return 'Proforma Invoice Flags';
	}

	public function getDescription()
	{
		return 'Set proforma and invoice flags in database.';
	}
}