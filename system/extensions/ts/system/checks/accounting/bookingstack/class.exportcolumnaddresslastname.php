<?php

class Ext_TS_System_Checks_Accounting_Bookingstack_ExportColumnAddressLastname extends GlobalChecks {
	public function getTitle()
	{
		return 'Booking stack';
	}

	public function getDescription()
	{
		return 'Corrects companies export column setting for addressee';
	}

	public function executeCheck() {

		$backup = Util::backupTable('ts_accounting_companies_columns_export');

		if (!$backup) {
			__pout('Backup error');
			return false;
		}

		$update = "UPDATE `ts_accounting_companies_columns_export` SET `column` = 'address_full_name' WHERE `column` = 'address_lastname'";

		\DB::executeQuery($update);

		return true;
	}

}