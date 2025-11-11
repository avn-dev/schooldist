<?php


class Ext_Thebing_System_Checks_AccommodationPaymentsMonth extends GlobalChecks
{
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_accommodations_payments');

		$sSql = "
			UPDATE
				`kolumbus_accommodations_payments`
			SET
				`inquiry_accommodation_id` = 0
			WHERE
				`payment_type` = 'month'
		";

		DB::executeQuery($sSql);

		return true;
	}

	public function getTitle()
	{
		return 'Check Accommodation Provider Payments';
	}

	public function getDescription()
	{
		return 'Seperation of student IDs and provider payments.';
	}

}