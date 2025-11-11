<?php

class Ext_TS_System_Checks_Payment_DocumentId extends GlobalChecks {

	public function getTitle() {
		return 'Create new relation between documents and payments';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		if(in_array('ts_documents_to_inquiries_payments', DB::listTables())) {
			Util::backupTable('ts_documents_to_inquiries_payments');
		}

		DB::executeQuery("DROP TABLE IF EXISTS `ts_documents_to_inquiries_payments`");

		DB::executeQuery("
			CREATE TABLE IF NOT EXISTS `ts_documents_to_inquiries_payments` (
			  `document_id` int(11) NOT NULL,
			  `payment_id` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

		DB::executeQuery("ALTER TABLE `ts_documents_to_inquiries_payments` ADD PRIMARY KEY( `document_id`, `payment_id`)");

		$sSql = "
			SELECT
				`kip`.`id`,
				COALESCE(`kid`.`id`, `kipo`.`inquiry_document_id`, `kipc`.`document_id`) `document_id`
			FROM
				`kolumbus_inquiries_payments` `kip` LEFT JOIN
				`kolumbus_inquiries_payments_items` `kipi` ON
					`kipi`.`payment_id` = `kip`.`id` AND
					`kipi`.`active` = 1 LEFT JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`id` = `kipi`.`item_id` LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kidvi`.`version_id` LEFT JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `kidv`.`document_id` LEFT JOIN
				`kolumbus_inquiries_payments_overpayment` `kipo` ON
					`kipo`.`payment_id` = `kip`.`id` AND
					`kipo`.`active` = 1 LEFT JOIN
				`kolumbus_inquiries_payments_creditnotes` `kipc` ON
					`kipc`.`payment_id` = `kip`.`id` AND
					`kipc`.`active` = 1
			WHERE
				`kip`.`active` = 1
			GROUP BY
				`kip`.`id`
		";

		$aResult = (array)DB::getQueryRows($sSql);

		$oStmt = DB::getPreparedStatement("
			REPLACE INTO
				`ts_documents_to_inquiries_payments`
			SET
				`document_id` = ?,
				`payment_id` = ?
		");

		foreach($aResult as $aRow) {
			if(empty($aRow['document_id'])) {
				$this->logInfo('No document found for payment '.$aRow['id']);
			} else {

				$this->logInfo('Found document '.$aRow['document_id'].' for payment '.$aRow['id']);

				DB::executePreparedStatement($oStmt, array(
					(int)$aRow['document_id'],
					(int)$aRow['id']
				));
			}
		}

		return true;

	}

}