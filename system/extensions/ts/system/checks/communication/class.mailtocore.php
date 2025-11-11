<?php

class Ext_TS_System_Checks_Communication_MailToCore extends GlobalChecks {
	
	public function getDescription() 
	{
		return 'Copy core mail database table to school';
	}
	
	public function getTitle()
	{
		return 'Mail to core'; 
	}
	
	public function executeCheck()
	{
		
		$aTables = DB::listTables();
		if(in_array('kolumbus_email_accounts', $aTables)) {
				
		$bBackup = Ext_Thebing_Util::backupTable('kolumbus_email_accounts');
		
		if(!$bBackup) {
			return false;
		}

		// Es kann sein, dass diese Spalte nicht da ist. Also sicherheitshalber erstellen!
		DB::addField('kolumbus_email_accounts', 'creator_id', "INT NOT NULL DEFAULT '0'", 'active', 'INDEX');

		$sSql = "
			INSERT INTO
				`tc_communication_emailaccounts`
				(
				`id`,
				`changed`,
				`created`,
				`active`,
				`editor_id`,
				`creator_id`,
				`email`,
				`smtp`,
				`smtp_host`,
				`smtp_user`,
				`smtp_pass`,
				`smtp_port`,
				`smtp_connection`
				)
			SELECT
				`kea`.`id`, 
				`kea`.`changed`, 
				`kea`.`created`,
				`kea`.`active`,
				`kea`.`creator_id`,
				`kea`.`creator_id`, 
				`kea`.`email`, 
				`kea`.`smtp`, 
				`kea`.`smtp_host`,
				`kea`.`smtp_user`, 
				`kea`.`smtp_pass`, 
				`kea`.`smtp_port`, 
				`kea`.`smtp_connection` 
			FROM
				`kolumbus_email_accounts` as `kea`
		";
		DB::executeQuery($sSql);
		
		
		$sSql = "
			DROP TABLE
				`kolumbus_email_accounts`
		";
		DB::executeQuery($sSql);
		}
		return true;
	}

}

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 * 
 * 
 * Emailtabelle Schule:
 * kolumbus_email_accounts
 * 
 * Spalten:
 * -------
 * id 	
 * changed 	
 * created 	
 * active 	
 * creator_id 	
 * client_id 	
 * email 	
 * smtp 	
 * smtp_host 	
 * smtp_user 	
 * smtp_pass 	
 * smtp_port 	
 * smtp_connection 	
 * 
 * 
 * Emailtabelle in Core:
 * tc_communication_emailaccounts
 * 
 * Spalten:
 * --------
 * id 	
 * changed 	
 * created 	
 * active 	
 * editor_id 	
 * creator_id 	
 * email 	
 * smtp 	
 * smtp_host 	
 * smtp_user 	
 * smtp_pass 	
 * smtp_port 	
 * smtp_connection 	
 * imap 	
 * imap_user 	
 * imap_pass 	
 * imap_host 	
 * imap_port 	
 * imap_connection 	
 * imap_filter 	
 * imap_closure
 * 
 * kolumbus_email_accounts wird benutzt in:
 * ---------------------------------------
 * dev.school.thebing.com:
 * /system/extensions/thebing/admin/class.emailaccounts.php (15)
 * /system/extensions/thebing/class.mail.php (6)
 * 
 * 
 * Ext_Thebing_Mail wird benutzt in:
 * ----------------------------------
 * dev.school.thebing.com:
 * /admin/extensions/thebing/admin/class.emailaccounts.php (7 ,12)
 * /system/extensions/thebing/class.communication.php (2860)
 * /system/extensions/thebing/class.email.php (19)
 * /system/extensions/thebing/class.mail.php (4 , 11 , 137)
 * /system/extensions/thebing/class.util.php (702)
 * /system/extensions/thebing/mail/class.data.php (3)
 * /system/extensions/thebing/thebing.backend.wdmail.php
 * 
 * dev.core.thebing.com:
 * /system/extensions/tc/communication/class.emailaccount.php (213)
 * 
 * 

 * 
 * 
 * tc_communication_emailaccounts wird benutzt in:
 * --------------------------------------------------
 * dev.core.thebing.com:
 * /system/extensions/tc/cummonication/class.emailaccount.php (11)
 * /system/extensions/tc/cummonication/class.imap.php (64)
 * /system/extensions/tc/cummonication/emailaccount/class.accessmatrix.php (11)
 * 
 * 
 * Ext_TC_Communication_EmailAccount wird benutzt in:
 * ----------------------------------------------------
 * dev.core.thebing.com:
 * /gui2/page/Tc_communication_category (5)
 * /admin/extensions/tc/admin/communication/emailaccount.html (8, 12, 87, 122, 134)
 * /admin/extensions/tc/config.html (5)
 * /system/extensions/tc/class.communication.php (843)
 * /system/extensions/tc/communication/class.emailaccount.php (9 ,17 ,34 ,115 ,218 ,344 , 361)
 * /system/extensions/tc/communication/class.imap.php (3)
 * /system/extensions/tc/communication/class.wdmail.php (41)
 * /system/extensions/tc/communication/emailaccount/class.accessmatrix.php (9)
 * /system/extensions/tc/communication/emailaccount/gui2/class.data.php (8 , 38, 177, 143)
 * /system/extensions/tc/communication/gui2/class.data.php (206)
 * /system/extensions/tc/user/format/class.email.php (19)
 * /system/extensions/tc/user/selection/class.emailaccounts.php (21)
 * 
 * dev.school.thebing.com:
 * /system/extensions/thebing/class.util.php (702)
 * 
 * dev.agency.thebing.com:
 * /admin/extensions/ta/config.html (8)
 * /system/extensions/ta/class.util.php (134)
 * /system/extensions/ta/communication/emailaccount/gui2/class.data.php
 * /system/extensions/ta/thirdparty/synergee/gui2/class.selection.php
 * 
 * 
 */



