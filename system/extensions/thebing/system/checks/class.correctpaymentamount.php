<?php

class Ext_Thebing_System_Checks_CorrectPaymentAmount extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Correct Payment Amount';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Calculate all payments to fix display problems';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Ext_Thebing_Util::backupTable('kolumbus_inquiries');

		$sSql = "
					SELECT
						`ki`.`id`
					FROM
						`kolumbus_inquiries` `ki` INNER JOIN
						`kolumbus_inquiries_documents` `kid` ON
							`kid`.`inquiry_id` = `ki`.`id` INNER JOIN
						`kolumbus_inquiries_documents_versions` `kidv` ON
							`kidv`.`document_id` = `kid`.`id` INNER JOIN
						`kolumbus_inquiries_documents_versions_items` `kidvi` ON
							`kidvi`.`version_id` = `kidv`.`id` INNER JOIN
 						`kolumbus_inquiries_payments_items` `kipi` ON
							`kipi`.`item_id` = `kidvi`.`id` INNER JOIN
 						`kolumbus_inquiries_payments` `kip` ON
							`kip`.`id` = `kipi`.`payment_id`
					WHERE
						`kip`.`active` > 0 AND
						`kipi`.`active` > 0 AND
						`ki`.`active` > 0 AND
						`kid`.`active` > 0 AND
						`kidv`.`active` > 0 AND
						`kidvi`.`active` > 0
					GROUP BY
						`ki`.`id`
						";
		$aResult = DB::getQueryData($sSql);

		foreach((array)$aResult as $aData){
			$oInquiry = Ext_TS_Inquiry::getInstance($aData['id']);
			$oInquiry->calculatePayedAmount();
		}

		return true;

	}

}