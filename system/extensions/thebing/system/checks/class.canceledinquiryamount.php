<?php


class Ext_Thebing_System_Checks_CanceledInquiryAmount extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_inquiries');

		$aDocumentTypesWithoutProforma = array(
			'brutto',
			'netto',
			'brutto_diff',
			'brutto_diff_special',
			'netto_diff',
			'credit_brutto',
			'credit_netto',
			'credit',
			'storno',
		);

		$sSql = "
			SELECT
				`cdb1`.`customerNumber`,
				`ki`.`id` `inquiry_id`,
				`kidv`.`tax` `tax_type`,
				`kidvi`.*
			FROM
				`kolumbus_inquiries` `ki` INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`inquiry_id` = `ki`.`id` AND
					`kid`.`active` = 1 AND
					`kid`.`type` IN(:document_types) INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`document_id` = `kid`.`id` AND
					`kidv`.`active` = 1 AND
					`kidv`.`id` = `kid`.`latest_version` INNER JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`version_id` = `kidv`.`id` AND
					`kidvi`.`active` = 1 INNER JOIN
				`customer_db_1` `cdb1` ON
					`cdb1`.`id` = `ki`.`idUser` AND
					`cdb1`.`active` = 1 INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ki`.`crs_partnerschool` AND
					`cdb2`.`active` = 1
			WHERE
				`ki`.`active` = 1 AND
				`ki`.`canceled` > 0
			ORDER BY
				`ki`.`id`,
				`kid`.`id`
		";

		$aSql = array(
			'document_types' => $aDocumentTypesWithoutProforma
		);

		$aResult	= (array)DB::getPreparedQueryData($sSql, $aSql);
		$aAmountAll	= array();

		foreach($aResult as $aRowData)
		{
			if( $aRowData['calculate'] == 0 )
			{
				continue;
			}

			$iInquiryId			= (int)$aRowData['inquiry_id'];

			$fAmountWithoutProvision	= $aRowData['amount'];
			$fAmountProvision			= $aRowData['amount_provision'];

			$fAmountNet			= $fAmountWithoutProvision - $fAmountProvision;
			$fFactorDiscount	= $aRowData['amount_discount'];

			$fAmountDiscount	= $fAmountNet * $fFactorDiscount / 100;
			$fAmountWithoutTax	= $fAmountNet - $fAmountDiscount;

			$fAmount			= $fAmountWithoutTax;

			//zzgl Steuern
			if( $aRowData['tax_type'] == 2)
			{
				$fAmount = $fAmountWithoutTax + ($fAmountWithoutTax * $aRowData['tax'] / 100);
			}

			if(
				!isset($aAmountAll[$iInquiryId])
			)
			{
				$aAmountAll[$iInquiryId] = 0;
			}

			$aAmountAll[$iInquiryId] += $fAmount;
		}

		foreach($aAmountAll as $iInquiryId => $fTotalAmount)
		{
			$iInquiryId = (int)$iInquiryId;

			if($iInquiryId > 0)
			{
				$aUpdateData = array(
					'amount'			=> (float)$fTotalAmount,
					'canceled_amount'	=> (float)$fTotalAmount,
				);

				$sWhere = ' id = '.$iInquiryId;

				DB::updateData('kolumbus_inquiries', $aUpdateData, $sWhere);
			}
		}

		return true;
	}

	public function getTitle()
	{
		return 'Check Cancelled Students';
	}

	public function getDescription()
	{
		return 'Check and recalculate the total amount for canceled students.';
	}
}