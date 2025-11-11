<?php

class Ext_Thebing_System_Checks_Paymentmethod extends GlobalChecks
{
	public function isNeeded(){
		global $user_data;

		return true;

	}
	
	public function executeCheck(){
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_inquiries');


		$sSql = "ALTER TABLE
						`kolumbus_inquiries`
					CHANGE
						`payment_methode`
						`payment_methode`
						TINYINT( 1 )
						NOT NULL
						DEFAULT '1'
						COMMENT '0=>Netto vor Anreise|1=>Brutto vor Anreise|2=>Netto bei Anreise|3=>Brutto bei Anreise'
		";

		DB::executeQuery($sSql);



		$sSql = "UPDATE
						`kolumbus_inquiries`
					SET
						`payment_methode` = 1
					WHERE
						`payment_methode` < 0
				";

		DB::executeQuery($sSql);
		

		return true;
	}

	public function getTitle()
	{
		return 'Payment Method';
	}

	public function getDescription()
	{
		return 'Import payment methods of into new structure.';
	}
}