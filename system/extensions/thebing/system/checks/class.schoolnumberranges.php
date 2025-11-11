<?php

class Ext_Thebing_System_Checks_SchoolNumberRanges extends GlobalChecks
{
	/**
	 * Get the check title
	 * 
	 * @return string
	 */
	public function getTitle() {
		$sTitle = 'Update number range table structure';
		return $sTitle;
	}


	/**
	 * Get the check description
	 * 
	 * @return string
	 */
	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}


	/**
	 * Execute the check
	 * 
	 * @return bool
	 */
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$bSuccess = Ext_Thebing_Util::backupTable('customer_db_2');

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aFields = array(
			'customer'	=> array(
				'format'	=> "`ext_332`",
				'digits'	=> "`count_digits_customer`",
				'offset'	=> "`ext_331`"
			),
			'invoice'	=> array(
				'format'	=> "CONCAT(`ext_308`,`ext_309`)",
				'digits'	=> "`count_digits`",
				'offset'	=> 1
			),
			'proforma'	=> array(
				'format'	=> "CONCAT(`ext_328`,`ext_329`)",
				'digits'	=> "`count_digits_proforma`",
				'offset'	=> 1
			),
			'receipt_payment'	=> array(
				'format'	=> "''",
				'digits'	=> 3,
				'offset'	=> 1
			),
			'receipt_invoice'	=> array(
				'format'	=> "''",
				'digits'	=> 3,
				'offset'	=> 1
			),
			'receipt_total'	=> array(
				'format'	=> "''",
				'digits'	=> 3,
				'offset'	=> 1
			)
		);

		$sSql = "TRUNCATE TABLE `kolumbus_number_ranges`";
		DB::executeQuery($sSql);

		foreach((array)$aFields as $sType => $aData)
		{
			$sSql = "
				INSERT INTO
					`kolumbus_number_ranges`
					(
						`created`,
						`client_id`,
						`school_id`,
						`type`,
						`offset_abs`,
						`format`,
						`digits`
					)
				SELECT
					NOW(),
					`idClient`,
					`id`,
					:sType,
					" . $aData['offset'] . ",
					" . $aData['format'] . ",
					" . $aData['digits'] . "
				FROM
					`customer_db_2`
				WHERE
					`active` = 1
			";
			$aSql = array(
				'sType'		=> $sType
			);
			DB::executePreparedQuery($sSql, $aSql);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if($bSuccess)
		{
			$sSQL = "
				ALTER TABLE
					`customer_db_2`
				DROP
					`ext_331`,
				DROP
					`ext_332`,
				DROP
					`ext_308`,
				DROP
					`ext_309`,
				DROP
					`ext_328`,
				DROP
					`ext_329`,
				DROP
					`count_digits`,
				DROP
					`count_digits_customer`,
				DROP
					`count_digits_proforma`
			";
			DB::executeQuery($sSQL);
		}

		return true;
	}
}