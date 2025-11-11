<?php

class Ext_TC_System_Checks_Communication_EmailAccountOAuth2 extends GlobalChecks
{
	public function getTitle()
	{
		return 'E-mail Account';
	}

	public function getDescription()
	{
		return 'Update e-mail account settings for oauth2 process';
	}

	public function executeCheck()
	{
		set_time_limit(120);
		ini_set("memory_limit", '1024M');

		if(!\Util::backupTable('tc_communication_emailaccounts')) {
			__pout('Backup error');
			return false;
		}

		$update = "
			UPDATE 
				`tc_communication_emailaccounts` 
			SET 
				`changed`=`changed`, 
				`smtp_auth`='oauth2', 
				`imap_auth`='oauth2'
			WHERE 
				`active` = 1 AND 
				(`smtp_host`='smtp.gmail.com' OR `smtp_host`='smtp.office365.com') AND 
				(`smtp_auth`='password' OR `imap_auth`='password')
		";

		\DB::executeQuery($update);

		return true;
	}

}