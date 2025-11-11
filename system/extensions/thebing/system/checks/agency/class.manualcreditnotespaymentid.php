<?php

/**
 * Spalte »payment_id« von Zahlungen von manuellen Gutschriften befüllen #6359
 */
class Ext_Thebing_System_Checks_Agency_ManualCreditnotesPaymentId extends GlobalChecks {

	public function getTitle() {
		return 'Check manual creditnote payments';
	}

	public function getDescription() {
		return self::getTitle();
	}

	public function executeCheck() {

		Util::backupTable('kolumbus_agencies_manual_creditnotes_payments');

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`kamcp`.`id`,
				`kipi`.`payment_id`
			FROM
				`kolumbus_agencies_manual_creditnotes_payments` `kamcp` LEFT JOIN
				`kolumbus_inquiries_payments_items` `kipi` ON
					`kipi`.`id` = `kamcp`.`payment_item_id`
			WHERE
				`kamcp`.`payment_id` = 0
		";

		$aResult = (array)DB::getQueryRows($sSql);
		foreach($aResult as $aPayment) {

			// mCN-Payments mit payment_item_id 0 sind verloren
			if(empty($aPayment['payment_id'])) {
				continue;
			}

			$sSql = "
				UPDATE
					`kolumbus_agencies_manual_creditnotes_payments`
				SET
					`payment_id` = :payment_id
				WHERE
					`id` = :id
			";

			DB::executePreparedQuery($sSql, $aPayment);
		}

		DB::commit(__CLASS__);

		return true;
	}
}