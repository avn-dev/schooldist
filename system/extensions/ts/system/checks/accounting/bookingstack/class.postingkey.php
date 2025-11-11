<?php

/**
 * Befüllt den Buchungsschlüssel der Spalte der Einträge im Buchungsstapel
 */
class Ext_TS_System_Checks_Accounting_Bookingstack_Postingkey extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		Util::backupTable('ts_booking_stacks');
		DB::begin('Ext_TS_System_Checks_Accounting_Bookingstack_Postingkey');

		$sSql = "
			UPDATE
				`ts_booking_stacks` `ts_bs` JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `ts_bs`.`document_id` JOIN
				`ts_companies_postingkeys` ts_cp ON
					ts_cp.`document_type` = `kid`.`type` AND
					ts_cp.`company_id` = `ts_bs`.`company_id`
			SET
				`ts_bs`.`posting_key` = ts_cp.`posting_key`
		";

		DB::executeQuery($sSql);
		DB::commit('Ext_TS_System_Checks_Accounting_Bookingstack_Postingkey');
	}

	public function getTitle()
	{
		return 'Booking stack posting key';
	}

	public function getDescription()
	{
		return 'Fills booking stack entries with corresponding posting key.';
	}
}