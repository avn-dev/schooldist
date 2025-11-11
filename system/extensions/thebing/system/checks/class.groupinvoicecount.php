<?php

class Ext_Thebing_System_Checks_GroupInvoiceCount extends GlobalChecks
{
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');
		
		$sSql = "
			SELECT 
				`ki`.`id`,
				`ki`.`group_id`,
				(
					SELECT
						COUNT(*)
					FROM
						`kolumbus_inquiries_documents`
					WHERE
						`inquiry_id` = `ki`.`id` AND
						`type` IN('brutto', 'netto') AND
						`active` = 1
				) `count_documents_self`,
				(
					SELECT
						COUNT(*)
					FROM
						`kolumbus_inquiries_documents` as `kid2` INNER JOIN
						`ts_inquiries` as `ki2` ON
							`ki2`.`id` = `kid2`.`inquiry_id` AND
							`ki2`.`active` = 1
					WHERE
						`ki2`.`group_id` = `ki`.`group_id` AND
						`type` IN('brutto', 'netto') AND
						`kid2`.`active` = 1
				) `count_documents_all`,
				(
					SELECT
						COUNT(*)
					FROM
						`ts_inquiries` as `ki3`
					WHERE
						`ki3`.`group_id` = `ki`.`group_id` AND
						`ki3`.`active` = 1 AND
						(
							SELECT
								COUNT(*)
							FROM
								`kolumbus_inquiries_documents`
							WHERE
								`inquiry_id` = `ki3`.`id` AND
								`type` IN('brutto', 'netto') AND
								active = 1
						) > 0
				) `count_inquiries`
			FROM 
				`ts_inquiries` `ki`
			where 
				`ki`.`group_id` > 0 AND 
				`ki`.`active` = 1 AND
				`ki`.`created` >= '2012-01-01 00:00:00'
			HAVING 
				`count_documents_self` > 0 AND
				`count_documents_self` != `count_documents_all` / `count_inquiries`
		";
		
		$aResult = (array)DB::getQueryRows($sSql);
		
		if(!empty($aResult))
		{
			$oMail = new WDMail();
			$oMail->subject = 'Group Invoice Bug';

			$sText = '';
			$sText = $_SERVER['HTTP_HOST']."\n\n";
			$sText .= date('Y-m-d H:i:s')."\n\n";
			
			$sText .= print_r($aResult, 1);

			$oMail->text = $sText;

			$oMail->send(array('m.durmaz@thebing.com'));
		}
		
		return true;
	}
	
	public function getDescription()
	{
		return 'Check invoice numbers of group members.';
	}
	
	public function getTitle()
	{
		return 'Check Group Invoices';
	}
}