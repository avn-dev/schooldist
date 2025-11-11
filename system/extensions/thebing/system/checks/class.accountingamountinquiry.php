<?php


class Ext_Thebing_System_Checks_AccountingAmountInquiry extends GlobalChecks 
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');
		
		Ext_Thebing_Util::backupTable('kolumbus_inquiries_payments');
		
		$sSql = "
			SELECT
				`kip`.`amount_inquiry` `calculated_amount_inquiry`,
				`kip`.`amount_school` `calculated_amount_school`,
				SUM(`kipi`.`amount_inquiry`) `sum_amount_inquiry`,
				SUM(`kipi`.`amount_school`) `sum_amount_school`
			FROM
				`kolumbus_inquiries_payments` `kip` INNER JOIN
				`kolumbus_inquiries_payments_items` `kipi` ON
					`kipi`.`payment_id` = `kip`.`id` AND
					`kipi`.`active` = 1
			WHERE
				`kip`.`active` = 1
			GROUP BY
				`kip`.`id`
			HAVING
				`calculated_amount_inquiry` < `sum_amount_inquiry` OR
				`calculated_amount_school` < `sum_amount_school`
		";
		
		$aResult = DB::getQueryRows($sSql);
		__uout($aResult, 'Mehmet'); 


		return true;
	}
	
	public function getTitle()
	{
		$sTitle = 'Check Payments';
		return $sTitle;
	}

	public function getDescription()
	{
		$sDescription = 'Check amount calculation in payments';
		return $sDescription;
	}
}
