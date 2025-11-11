<?php


class Ext_Thebing_System_Checks_PaymentVersionError extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Check Payment Errors';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Check anomalies in existing payments.';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$sSql = "
			SELECT
				`kip`.`id`,
				`kip`.`active` AS `payment_active`,
				`kipi`.`active` AS `payment_item_active`,
				`kidvi`.`active` AS `version_item_active`,
				`kidv`.`active` AS `version_active`,
				`kid`.`active` AS `document_active`,
				`ki`.`active` AS `inquiry_active`,
				`ki`.`id` AS `inquiry_id`,
				`kid`.`id` AS `document_id`,
				`kidv`.`id` AS `version_id`,
				`kipi`.`id` AS `payment_item_id`
			FROM
				`kolumbus_inquiries_payments` AS `kip` INNER JOIN
				`kolumbus_inquiries_payments_items` AS `kipi` ON
					`kipi`.`payment_id` = `kip`.`id` INNER JOIN
				`kolumbus_inquiries_documents_versions_items` AS `kidvi` ON
					`kidvi`.`id` = `kipi`.`item_id` INNER JOIN
				`kolumbus_inquiries_documents_versions` AS `kidv` ON
					`kidv`.`id` = `kidvi`.`version_id` INNER JOIN
				`kolumbus_inquiries_documents` AS `kid` ON
					`kid`.`id` = `kidv`.`document_id` INNER JOIN
				`kolumbus_inquiries` AS `ki` ON
					`ki`.`id` = `kid`.`inquiry_id`
			WHERE
				`kidv`.`active` = 0 AND
				`kip`.`active` = 1
		";

		$aResult	= (array)DB::getQueryRows($sSql);

		if(!empty($aResult))
		{
			$oMail = new WDMail();
			$oMail->subject = "PaymentVersionErrors";

			$sText = $_SERVER['HTTP_HOST']."\n\n";
			$sText .= date('YmdHis')."\n\n";
			$sText .= "Errors \n\n";
			$sText .= print_r($aResult,1);
			$sText .= "\n\n";

			$oMail->text = $sText;
			$oMail->send(array('m.durmaz@thebing.com'));
		}

		return true;
	}

}