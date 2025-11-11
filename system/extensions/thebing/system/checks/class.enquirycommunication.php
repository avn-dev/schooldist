<?php


class Ext_Thebing_System_Checks_EnquiryCommunication extends GlobalChecks
{
	public function getTitle()
	{
		return 'Change Enquiry Communication-Application';
	}
	
	public function getDescription()
	{
		return 'Change enquiry application type for communication.';
	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');
		
		$sSql = "
			UPDATE
				`kolumbus_email_templates_applications`
			SET
				`application` = 'enquiry'
			WHERE
				`application` = 'customer'
		";
		
		DB::executeQuery($sSql);
		
		return true;
	}
}