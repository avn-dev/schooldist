<?php

class Ext_Thebing_System_Checks_Payments_PaymentMethodAllSchools extends GlobalChecks {

	public function getTitle() {
		return 'All schools: Payment methods';
	}

	public function getDescription() {
		return 'Make payment methods available for all schools.';
	}

	public function executeCheck() {

		$aFields = DB::describeTable('kolumbus_payment_method', true);

		if(isset($aFields['idSchool'])) {

			Util::backupTable('kolumbus_payment_method');

			DB::executeQuery("TRUNCATE `kolumbus_payment_method_schools`");

			$sSql = "
				INSERT INTO
					`kolumbus_payment_method_schools`
				SELECT
					`id` `payment_method_id`,
					`idSchool` `school_id`
				FROM
					`kolumbus_payment_method`
			";

			DB::executeQuery($sSql);

			DB::executeQuery("ALTER TABLE `kolumbus_payment_method` DROP `idSchool`");

		}

		return true;

	}

}
