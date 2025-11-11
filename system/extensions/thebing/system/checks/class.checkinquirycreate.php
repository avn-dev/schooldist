<?php

class Ext_Thebing_System_Checks_CheckInquiryCreate extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('customer_db_1');
		Ext_Thebing_Util::backupTable('kolumbus_inquiries');

		// Fehlende create Werte in Kundentabelle setzen
		$sSql = "
					UPDATE
						`customer_db_1`
					SET
						`last_changed` = `last_changed`,
						`created` = `last_changed`
					WHERE
						`created` = 0
				";
		DB::executeQuery($sSql);

		// Fehlende create Werte in Buchungstabelle setzen
		$sSql = "
			UPDATE
				`kolumbus_inquiries` `ki`
			SET
				`changed` = `changed`,
				`created` = (
					SELECT
						`cdb1`.`created`
					FROM
						`customer_db_1` `cdb1`							
					WHERE
						`cdb1`.`id` = `ki`.`idUser`
					LIMIT 1
				)
			WHERE
				`created` = 0
		";
		DB::executeQuery($sSql);

		return true;

	}

	public function getTitle()
	{
		return 'Check create timestamp in inquiry table';
	}

	public function  getDescription()
	{
		return '...';
	}
}
