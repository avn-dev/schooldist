<?php

class Ext_Thebing_System_Checks_Frontend_Form_PaymentProvider extends GlobalChecks {

	public function getTitle() {
		return 'Update registration form settings';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$this->migrateSchoolSettings();
		$this->migrateFormSettings();

		return true;

	}

	private function migrateSchoolSettings() {

		if(!DB::getDefaultConnection()->checkField('customer_db_2', 'paypal_client_sandbox', true)) {
			return true;
		}

		Util::backupTable('customer_db_2');

		$sSql = "
			SELECT
				*
			FROM
				`customer_db_2`
			WHERE
				`paypal_client_sandbox` != '' OR
				`paypal_client_id` != '' OR
				`paypal_client_secret` != ''
		";

		$aResult = (array)DB::getQueryRows($sSql);
		foreach($aResult as $aRow) {
			$oSchool = Ext_Thebing_School::getObjectFromArray($aRow);
			$oSchool->setAttributeTypeData('paypal_client_sandbox', $oSchool->paypal_client_sandbox);
			$oSchool->setAttributeTypeData('paypal_client_id', $oSchool->paypal_client_id);
			$oSchool->setAttributeTypeData('paypal_client_secret', $oSchool->paypal_client_secret);
			$oSchool->save();
		}

		DB::executeQuery("ALTER TABLE `customer_db_2` DROP `paypal_client_sandbox`");
		DB::executeQuery("ALTER TABLE `customer_db_2` DROP `paypal_client_id`");
		DB::executeQuery("ALTER TABLE `customer_db_2` DROP `paypal_client_secret`");

		return true;

	}

	private function migrateFormSettings() {

		if(!DB::getDefaultConnection()->checkField('kolumbus_forms_schools', 'use_paypal', true)) {
			return true;
		}

		Util::backupTable('kolumbus_forms_schools');

		DB::addField('kolumbus_forms_schools', 'payment_provider', "VARCHAR(255) NOT NULL", 'generate_invoice');
		DB::addField('kolumbus_forms_schools', 'payment_method', "SMALLINT UNSIGNED NOT NULL DEFAULT '0'", 'payment_provider');

		$sSql = "
			SELECT
				`form_id`,
				`school_id`
			FROM
				`kolumbus_forms_schools`
			WHERE
				`use_paypal` = 1
		";

		$aResult = (array)DB::getQueryRows($sSql);

		foreach($aResult as $aRow) {

			DB::updateData('kolumbus_forms_schools', [
				'payment_provider' => 'TsFrontend\Handler\Payment\Legacy\PayPal',
				'payment_method' => $this->getPaymentMethod($aRow['school_id'])
			], " `form_id` = {$aRow['form_id']} AND `school_id` = {$aRow['school_id']} ");

		}

		DB::executeQuery("ALTER TABLE `kolumbus_forms_schools` DROP `use_paypal`");

		return true;

	}

	private function getPaymentMethod($iSchoolId) {

		$aPaymentMethods = Ext_Thebing_Admin_Payment::getPaymentMethods(false, $iSchoolId);
		foreach($aPaymentMethods as $oPaymentMethod) {
			if($oPaymentMethod->type === 'paypal') {
				return $oPaymentMethod->id;
			}
		}

		return 0;

	}

}
