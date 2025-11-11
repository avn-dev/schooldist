<?php

/**
 * Sucht doppelte Rechnungsnummern und verschickt eine E-Mail an den Support 
 */
class Ext_Thebing_System_Checks_Payments_WrongCreditnoteItems extends GlobalChecks {
	
	public function getTitle() {
		$sTitle = 'Search for wrong payment items';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){
		global $system_data;

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');
		
		$sSql = "
			SELECT 
				`p`.`id` `payment_id`, 
				`d`.`document_number`, 
				`d`.`id` `document_id`, 
				`d`.`inquiry_id` `inquiry_id`
			FROM  
				`kolumbus_inquiries_payments` `p` JOIN 
				`kolumbus_inquiries_payments_items` `pi` ON 
					`p`.`id` = `pi`.`payment_id` JOIN 
				`kolumbus_inquiries_documents_versions_items` `dvi` ON 
					`pi`.`item_id` = `dvi`.`id` JOIN 
				`kolumbus_inquiries_documents_versions` `dv` ON 
					`dvi`.`version_id` = `dv`.`id` JOIN 
				`kolumbus_inquiries_documents` `d` ON 
					`dv`.`document_id` = `d`.`id`
			WHERE 
				`d`.`type` = 'creditnote' AND
				`p`.`active` = 1 AND
				`d`.`active` = 1
			GROUP BY
				`d`.`document_number`
			";
		$aCreditnotes = DB::getQueryRows($sSql);

		if(!empty($aCreditnotes)) {

			$sMessageBody = json_encode($aCreditnotes);
			$sMessage = 'Payments mit Zuweisung zu Agenturgutschriften bei "'.$_SERVER['HTTP_HOST'].'"';

			$oMail			= new WDMail();
			$oMail->subject = $sMessage;
			$oMail->html	= $sMessageBody;
			$oMail->send('support@thebing.com');

		}

		return true;

	}

}
