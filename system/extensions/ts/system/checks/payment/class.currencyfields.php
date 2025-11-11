<?php

/**
 * kolumbus_inquiries_payments: currency_inquiry und currency_school befüllen
 */
class Ext_TS_System_Checks_Payment_CurrencyFields extends GlobalChecks
{
	public function getTitle()
	{
		return 'Update payments table';
	}

	public function getDescription()
	{
		return 'Make currency distinction more robust.';
	}

	public function executeCheck()
	{
		Util::backupTable('kolumbus_inquiries_payments');

		$sql = "
			SELECT
				kip.id,
				kipi.currency_inquiry item_currency_inquiry,
				kipi.currency_school item_currency_school,
				kipo.currency_inquiry overpayment_currency_inquiry,
				kipo.currency_school overpayment_currency_inquiry,
				ts_i.currency_id inquiry_currency,
				cdb2.currency school_currency
			FROM
			    kolumbus_inquiries_payments kip LEFT JOIN
				kolumbus_inquiries_payments_items kipi ON
					kipi.payment_id = kip.id LEFT JOIN
				kolumbus_inquiries_payments_overpayment kipo ON
					kipo.payment_id = kip.id LEFT JOIN 
				(
					ts_inquiries ts_i INNER JOIN
					ts_inquiries_journeys ts_ij INNER JOIN
					customer_db_2 cdb2
				) ON
				    ts_i.id = kip.inquiry_id AND
				    ts_ij.inquiry_id = ts_i.id AND
				    cdb2.id = ts_ij.school_id
			WHERE
			    kip.currency_inquiry = 0 OR
			    kip.currency_school = 0
			GROUP BY
			    kip.id
		";

		$payments = (array)DB::getQueryRows($sql);

		foreach ($payments as $payment) {

			if (
				!empty($payment['item_currency_inquiry']) &&
				!empty($payment['item_currency_school'])
			) {
				$currencyInquiry = $payment['item_currency_inquiry'];
				$currencySchool = $payment['item_currency_school'];
			} elseif (
				!empty($payment['overpayment_currency_inquiry']) &&
				!empty($payment['overpayment_currency_school'])
			) {
				$currencyInquiry = $payment['overpayment_currency_inquiry'];
				$currencySchool = $payment['overpayment_currency_school'];
			} elseif (
				!empty($payment['inquiry_currency']) &&
				!empty($payment['school_currency'])
			) {
				$currencyInquiry = $payment['inquiry_currency'];
				$currencySchool = $payment['school_currency'];
			}

			if (
				empty($currencyInquiry) ||
				empty($currencySchool)
			) {
				$this->logError('No currencies found for payment '.$payment['id']);
				continue;
			}

			DB::executePreparedQuery("
				UPDATE
					kolumbus_inquiries_payments
				SET
				    changed = changed,
				    currency_inquiry = :currency_inquiry, 
				    currency_school = :currency_school
				WHERE
				    id = :id
			", [
				'currency_inquiry' => $currencyInquiry,
				'currency_school' => $currencySchool,
				'id' => $payment['id']
			]);

		}

		return true;
	}
}
