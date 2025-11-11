<?php

class Ext_Thebing_System_Checks_InquirySchoolCheck extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		if(class_exists('Ext_Thebing_System_Checks_SchoolClientId')) {
			$oSchoolClientCheck = new Ext_Thebing_System_Checks_SchoolClientId;
			$oSchoolClientCheck->executeCheck();
		}
		
		$sSql = "
			SELECT
				`id`,
				`changed`,
				`created`,
				`crs_partnerschool`,
				`office`,
				`idUser`
			FROM
				`kolumbus_inquiries`
			WHERE
				`active` = 1 AND
				`office` RLIKE '^[0-9]*$' AND
				(
					`crs_partnerschool` = 0 OR
					`office` = '0' OR
					`idUser` = 0
				)
		";

		$aResult = DB::getQueryRows($sSql);

		$sSql = "
			SELECT
				`id`,
				`last_changed`,
				`created`,
				`office`,
				`ext_31`,
				`ext_1`,
				`ext_2`
			FROM
				`customer_db_1`
			WHERE
				`active` = 1 AND
				`office` RLIKE '^[0-9]*$' AND
				(
					`ext_31` = 0 OR
					`office` = '0'
				)
		";

		$aResultCustomer = DB::getQueryRows($sSql);

		if(
			!empty($aResult) || 
			!empty($aResultCustomer))
		{
			$sText = '';

			$oMail = new WDMail();
			$oMail->subject = "Schüler mit fehlerhaften Daten";
			$sText .= $_SERVER['HTTP_HOST']."\n\n";

			if(!empty($aResult))
			{
				$sText .= "-------------------------\n";
				$sText .= "Inquiries\n";
				foreach($aResult as $aRowData)
				{
					$sText .= "-------------------------\n" . print_r($aRowData, 1)."\n";
				}
			}

			if(!empty($aResultCustomer))
			{
				$sText .= "-------------------------\n";
				$sText .= "Customers\n";
				foreach($aResultCustomer as $aRowData)
				{
					$sText .= "-------------------------\n" . print_r($aRowData, 1)."\n";
				}
			}

			$oMail->text = $sText;
			$oMail->send(array('m.durmaz@thebing.com','support@thebing.com'));
		}

		return true;
	}

	public function getTitle()
	{
		return 'Search customers with errors';
	}

	public function  getDescription()
	{
		return 'Search customers with errors.';
	}
}