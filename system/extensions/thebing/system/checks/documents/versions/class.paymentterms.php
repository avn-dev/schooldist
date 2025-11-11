<?php

class Ext_Thebing_System_Checks_Documents_Versions_PaymentTerms extends GlobalChecks {

	public function getTitle() {
		return 'Migrate payment terms of existing documents';
	}

	public function getDescription() {
		return 'This could take some time.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		$aFields = DB::describeTable('kolumbus_inquiries_documents_versions', true);
		if(!isset($aFields['amount_prepay'])) {
			return true;
		}

		Util::backupTable('ts_inquiries');
		Util::backupTable('kolumbus_inquiries_documents_versions');

		DB::executeQuery("TRUNCATE ts_documents_versions_paymentterms");

		$sSql = "
			SELECT
				kidv.id,
				kidv.amount_prepay_due,
				kidv.amount_finalpay_due,
				kidv.amount_prepay,
				kidv.created,
				kidv.changed
			FROM
				kolumbus_inquiries_documents_versions kidv INNER JOIN
				kolumbus_inquiries_documents kid ON
					kid.id = kidv.document_id
			WHERE
				kid.type IN (:invoice_types)
			ORDER BY
				kidv.id
		";

		$oCollection = DB::getDefaultConnection()->getCollection($sSql, [
			'invoice_types' => (new Ext_Thebing_Inquiry_Document_Type_Search())->getSectionTypes('invoice_with_creditnote'),
		]);

		$iCounter = 0;
		foreach($oCollection as $aVersion) {

			$fAmountPrepay = 0;
			if(
				$aVersion['amount_prepay_due'] !== '0000-00-00' &&
				$aVersion['amount_prepay_due'] !== $aVersion['amount_finalpay_due'] &&
				round($aVersion['amount_prepay'], 2) != 0
			) {
				DB::insertData('ts_documents_versions_paymentterms', [
					'created' => $aVersion['created'],
					'changed' => $aVersion['changed'],
					'version_id' => $aVersion['id'],
					'setting_id' => 0,
					'type' => 'deposit',
					'date' => $aVersion['amount_prepay_due'],
					'amount' => $aVersion['amount_prepay']
				]);

				$fAmountPrepay = $aVersion['amount_prepay'];
			}

			if($aVersion['amount_finalpay_due'] !== '0000-00-00') {

				$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance($aVersion['id']);
				$fAmountFinalPay = $oVersion->getAmount() - $fAmountPrepay;

				DB::insertData('ts_documents_versions_paymentterms', [
					'created' => $aVersion['created'],
					'changed' => $aVersion['changed'],
					'version_id' => $aVersion['id'],
					'setting_id' => 0,
					'type' => 'final',
					'date' => $aVersion['amount_finalpay_due'],
					'amount' => $fAmountFinalPay
				]);

			}

			if($iCounter % 100 == 0) {
				WDBasic::clearAllInstances();
			}

			$iCounter++;

		}

		DB::executeQuery("ALTER TABLE ts_inquiries DROP amount_prepay_due, DROP amount_finalpay_due, DROP amount_prepay");

		DB::executeQuery("ALTER TABLE kolumbus_inquiries_documents_versions DROP amount_prepay_due, DROP amount_finalpay_due, DROP amount_prepay");

		return true;

	}

}
