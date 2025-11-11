<?php

class Ext_TS_System_Checks_Payment_Transactions extends GlobalChecks {

	public function getTitle() {
		return 'Initial generation of the debtors report';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2G');
		
		Util::backupTable('ts_accounts_transactions');
		
		DB::executeQuery("DROP TABLE IF EXISTS `ts_accounts_transactions`");
		DB::executeQuery("CREATE TABLE `ts_accounts_transactions` (`id` int(11) NOT NULL, `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `account_type` enum('agency','group','contact','sponsor') NOT NULL, `account_id` int(10) UNSIGNED NOT NULL, `amount` decimal(16,5) NOT NULL, `type` enum('invoice','proforma','payment') NOT NULL, `type_id` int(11) NOT NULL, `currency_iso` char(3) NOT NULL DEFAULT 'EUR', `due_date` date DEFAULT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		DB::executeQuery("ALTER TABLE `ts_accounts_transactions` ADD PRIMARY KEY (`id`), ADD KEY `due_date` (`due_date`), ADD KEY `created` (`created`), ADD KEY `type` (`type`), ADD KEY `account` (`account_type`,`account_id`), ADD KEY `type_combination` (`type`,`type_id`)");
		DB::executeQuery("ALTER TABLE `ts_accounts_transactions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
				
		// Proforma, Invoices, Credit notes
		$sSql = "
			SELECT
			   `kid`.`id` `document_id`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
				    `ts_ij`.`inquiry_id` = `ts_i`.`id` AND
				    `ts_ij`.`active` = 1  INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`entity` = 'Ext_TS_Inquiry' AND
					`kid`.`entity_id` = `ts_i`.`id` AND
					`kid`.`active` = 1
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_ij`.`school_id` IN (:schools) AND
				`kid`.`type` IN (:document_types)
			GROUP BY
				`kid`.`id`
		";

		$aResult = (array)\DB::getQueryCol($sSql, [
			'schools' => array_keys(Ext_Thebing_Client::getSchoolList(true)),
			'document_types' => \Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnote_and_manual_creditnote'),
		]);
		
		foreach($aResult as $iDocumentId) {
			$this->addProcess(['document_id'=>(int)$iDocumentId]);
		}
		
		// Payments
		$sSql = "
			SELECT
			   `kip`.`id`
			FROM
				`kolumbus_inquiries_payments` `kip` INNER JOIN
				`ts_inquiries` `ts_i` ON
				    `kip`.`inquiry_id` = `ts_i`.`id` AND
				    `ts_i`.`active` = 1
			WHERE
				`kip`.`active` = 1
			GROUP BY
				`kip`.`id`
		";
		$aPayments = (array)\DB::getQueryCol($sSql);
		
		foreach($aPayments as $iPaymentId) {
			$this->addProcess(['payment_id'=>(int)$iPaymentId]);
		}

		return true;
	}

	public function executeProcess(array $aData) {
		
		if(isset($aData['document_id'])) {
			$oDocument = Ext_Thebing_Inquiry_Document::getInstance($aData['document_id']);
			$oDocument->updateTransactions(false);
		} elseif(isset($aData['payment_id'])) {
			$oPayment = Ext_Thebing_Inquiry_Payment::getInstance($aData['payment_id']);
			$oPayment->updateTransaction();
		}
		
	}
	
}
