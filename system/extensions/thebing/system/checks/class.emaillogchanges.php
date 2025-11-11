<?php


class Ext_Thebing_System_Checks_EmailLogChanges extends GlobalChecks
{
	public function getDescription()
	{
		return 'Check for changes in Email-History';
	}
	
	public function getTitle()
	{
		return 'Check Email-Log Changes';
	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$sBackup1 = Util::backupTable('kolumbus_email_log');
		$sBackup2 = Util::backupTable('kolumbus_email_log_relations');
		
		//Klassennname hat sich verändert, auch anpassen bei den Logs
		$sSql = "
			UPDATE
				`kolumbus_email_log`
			SET
				`object` = 'Ext_TS_Inquiry',
				`created` = `created`
			WHERE
				`object` = 'Ext_Thebing_Inquiry'
		";
		
		DB::executeQuery($sSql);
		
		$sSql = "
			UPDATE
				`kolumbus_email_log_relations`
			SET
				`object` = 'Ext_TS_Inquiry'
			WHERE
				`object` = 'Ext_Thebing_Inquiry'
		";
		
		DB::executeQuery($sSql);
		
		//Alle Anfragen umstellen
		$sSql = "
			SELECT
				`ts_en`.`id` `enquiry_id`,
				`tc_co`.`id` `contact_id`,
				`old_inquiries`.`id` `old_inquiry_id`
			FROM
				`ts_enquiries` `ts_en` LEFT JOIN
				`ts_enquiries_to_inquiries` `ts_en_to_in` ON
					`ts_en_to_in`.`enquiry_id` = `ts_en`.`id` INNER JOIN
				`ts_enquiries_to_contacts` `ts_en_to_co` ON
					`ts_en_to_co`.`enquiry_id` = `ts_en`.`id` AND
					`ts_en_to_co`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `tc_c` ON
					`tc_c`.`id` = `ts_en_to_co`.`contact_id` AND
					`tc_c`.`active` = 1 INNER JOIN
				`__old_kolumbus_inquiries` `old_inquiries` ON
					`old_inquiries`.`idUser` = `tc_c`.`id`
			WHERE
				`ts_en`.`active` = 1
		";
		
		$aResult = DB::getQueryRows($sSql);
		
		foreach($aResult as $aRowData)
		{
			$sSql = "
				UPDATE
					`kolumbus_email_log`
				SET
					`object_id` = :object_id,
					`object` = :object,
					`application` = :application,
					`created` = `created`
				WHERE
					`object_id` = :old_object_id AND
					`object` = 'Ext_TS_Inquiry' AND
					`application` = 'customer'
			";
			
			$aSql = array(
				'object_id'		=> $aRowData['enquiry_id'],
				'object'		=> 'Ext_TS_Enquiry',
				'application'	=> 'enquiry',
				'old_object_id'	=> $aRowData['old_inquiry_id']
			);
			
			DB::executePreparedQuery($sSql, $aSql);
			
			$sSql = "
				UPDATE
					`kolumbus_email_log_relations` `kelr` INNER JOIN
					`kolumbus_email_log` `kel` ON
						`kel`.`id` = `kelr`.`log_id`
				SET
					`kelr`.`object_id` = :object_id,
					`kelr`.`object` = :object
				WHERE
					`kelr`.`object_id` = :old_object_id AND
					`kelr`.`object` = 'Ext_TS_Inquiry' AND
					`kel`.`application` = 'customer'
			";
			
			DB::executePreparedQuery($sSql, $aSql);
		}
		
		return true;
	}
}