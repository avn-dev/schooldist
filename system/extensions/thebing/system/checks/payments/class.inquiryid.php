<?php

/**
 * Check befüllt die inquiry_id von Payments
 */
class Ext_Thebing_System_Checks_Payments_InquiryId extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Update payment structure';
		return $sTitle;
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		Util::backupTable('kolumbus_inquiries_payments');

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`kip`.`id`,
				`kip`.`created`,
				`kid`.`entity_id` `inquiry_id`,
				GROUP_CONCAT(`kid`.`entity_id`) `inquiry_ids`,
				`kid2`.`entity_id` `overpayment_inquiry_id`,
				GROUP_CONCAT(`kid2`.`entity_id`) `overpayment_inquiry_ids`,
				`kid3`.`entity_id` `receipt_inquiry_id`,
				GROUP_CONCAT(`kid3`.`entity_id`) `kid3_inquiry_ids`
			FROM
				`kolumbus_inquiries_payments` `kip` LEFT JOIN
				`kolumbus_inquiries_payments_items` `kipi` ON
					`kipi`.`payment_id` = `kip`.`id` LEFT JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidvi`.`id` = `kipi`.`item_id` LEFT JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kidvi`.`version_id` LEFT JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `kidv`.`document_id` AND
					`kid`.`entity` = 'Ext_TS_Inquiry' LEFT JOIN
				-- Überbezahlungen
				`kolumbus_inquiries_payments_overpayment` `kipo` ON
					`kipo`.`payment_id` = `kip`.`id` LEFT JOIN
				`kolumbus_inquiries_documents` `kid2` ON
					`kid2`.`id` = `kipo`.`inquiry_document_id` AND
					`kid2`.`entity` = 'Ext_TS_Inquiry' LEFT JOIN
				-- Bezahlbelege
				`kolumbus_inquiries_payments_documents` `kipd` ON
					`kipd`.`payment_id` = `kip`.`id` LEFT JOIN
				`kolumbus_inquiries_documents` `kid3` ON
					`kid3`.`id` = `kipd`.`document_id` AND
					`kid3`.`entity` = 'Ext_TS_Inquiry'
			WHERE
				`kip`.`inquiry_id` = 0 AND
				`kip`.`type_id` IN (4, 5)
			GROUP BY
				`kip`.`id`
			ORDER BY
				NULL -- Unbenötigten filesort vermeiden
		";

		$oUpdateStatement = DB::getPreparedStatement("
			UPDATE
				`kolumbus_inquiries_payments`
			SET
				`inquiry_id` = ?
			WHERE
				`id` = ?
		");

		$oDb = DB::getDefaultConnection();
		$aPayments = $oDb->getCollection($sSql);
		$iLostPayments = 0;

		foreach($aPayments as $aPayment) {

			$iPaymentId = (int)$aPayment['id'];

			if(!empty($aPayment['inquiry_id'])) {
				// Inquiry-ID über Items
				$iInquiryId = (int)$aPayment['inquiry_id'];
			} elseif(!empty($aPayment['overpayment_inquiry_id'])) {
				// Inquiry-ID über Überbezahlung
				$iInquiryId = (int)$aPayment['overpayment_inquiry_id'];
			} elseif(!empty($aPayment['receipt_inquiry_id'])) {
				// Inquiry-ID über Bezahlbeleg
				$iInquiryId = (int)$aPayment['receipt_inquiry_id'];
			} else {
				$iLostPayments++;
				$this->logInfo('Could not find inquiry id for payment '.$iPaymentId.' (created: '.$aPayment['created'].')');
				continue;
			}

			DB::executePreparedStatement($oUpdateStatement, array(
				$iInquiryId, $iPaymentId
			));

			$this->logInfo('Set inquiry id of payment '.$iPaymentId.' to '.$iInquiryId);
		}

		DB::commit(__CLASS__);

		$this->logInfo('Iterated payments: '.count($aPayments));
		$this->logInfo('Lost payments: '.$iLostPayments);

		return true;
	}
}
