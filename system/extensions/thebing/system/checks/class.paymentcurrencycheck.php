<?php

/**
 * Check um zu überprüfen, ob es Datensätze mit Umrechnungsfehlern gibt
 */
class Ext_Thebing_System_Checks_PaymentCurrencyCheck extends GlobalChecks
{
	
	public function executeCheck()
	{
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$sSql = "
			SELECT
				`kipi`.*
			FROM
				`kolumbus_inquiries_payments_items` `kipi` INNER JOIN
				`kolumbus_inquiries_payments` `kip` ON
					`kip`.`id` = `kipi`.`payment_id` AND
					`kip`.`active` = 1
			WHERE
				`kipi`.`active` = 1 AND
				(
					`kipi`.`currency_inquiry` <= 0 OR
					`kipi`.`currency_school` <= 0
				) AND
				`kipi`.`created` >= :timepoint
		";
		
		$oDate = new WDDate();
		$oDate->set('2011', WDDate::YEAR);
		$oDate->set('01', WDDate::MONTH);
		$oDate->set('01', WDDate::DAY);
		$oDate->set('00:00:00', WDDate::TIMES);
		
		$sTimepoint = $oDate->get(WDDate::DB_TIMESTAMP);
		
		$aSql = array(
			'timepoint' => $sTimepoint,
		);
		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		
		if(
			!empty($aResult)
		)
		{
			$oMail = new WDMail();
			$oMail->subject = "Payment Item Currency Convert Error V2";

			$sText = $_SERVER['HTTP_HOST']."\n\n";
			$sText .= date('d.m.Y H:i:s')."\n\n";
			$sText .= print_r($aResult,1);

			$oMail->text = $sText;
			$oMail->send(array('m.durmaz@thebing.com'));
		}

		return true;
	}

	public function getTitle()
	{
		return 'Payment Currency Check';
	}

	public function getDescription()
	{
		return 'Check for currency in payments.';
	}
}