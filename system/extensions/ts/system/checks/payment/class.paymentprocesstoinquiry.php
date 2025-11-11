<?php

class Ext_TS_System_Checks_Payment_PaymentProcessToInquiry extends GlobalChecks {

	public function getTitle() {
		return 'Payment Process Migration';
	}

	public function getDescription() {
		return 'Migrate payment process links to always capture next expected payment.';
	}

	public function executeCheck() {

		$fields = DB::describeTable('ts_inquiries_payments_processes', true);
		if (!isset($fields['document_id'])) {
			return true;
		}

		Util::backupTable('ts_inquiries_payments_processes');

		DB::addField('ts_inquiries_payments_processes', 'inquiry_id', 'MEDIUMINT UNSIGNED NOT NULL', 'document_id', 'INDEX');

		DB::addField('ts_inquiries_payments_processes', 'capture', " ENUM('next','all') CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'next'", 'hash');

		DB::executeQuery("
			UPDATE
				`ts_inquiries_payments_processes` `ts_ipp` INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `ts_ipp`.`document_id`
			SET
				`ts_ipp`.`inquiry_id` = `kid`.`inquiry_id`,
			    `ts_ipp`.`changed` = `ts_ipp`.`changed`
		");

		// Sollte eigentlich nicht vorkommen
		DB::executeQuery("DELETE FROM `ts_inquiries_payments_processes` WHERE `inquiry_id` = 0");

		DB::executeQuery("ALTER TABLE `ts_inquiries_payments_processes` DROP INDEX `unique`");

		DB::executeQuery("ALTER TABLE `ts_inquiries_payments_processes` DROP `document_id`, DROP `paymentterm_index`");

		return true;

	}

}