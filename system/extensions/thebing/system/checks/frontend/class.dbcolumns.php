<?php

class Ext_Thebing_System_Checks_Frontend_DbColumns extends GlobalChecks {

	public function getTitle() {
		return 'Update registration form linkages';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		if(System::d('frontend_store_ips') === null) {
			System::s('frontend_store_ips', 1);
		}

		$this->migrateColumns('ts_inquiries', 'reg_form');
		$this->migrateColumns('ts_enquiries', 'form_enquiry');

		try {
			DB::executeQuery("ALTER TABLE `ts_inquiries` DROP `ip`");
		} catch(DB_QueryFailedException $e) {
			//
		}

		Ext_TS_Inquiry::deleteTableCache();
		Ext_TS_Enquiry::deleteTableCache();

		return true;

	}

	private function migrateColumns($sTable, $sColumn) {

		if(!DB::getDefaultConnection()->checkField($sTable, $sColumn, true)) {
			return;
		}

		Util::backupTable($sTable);

		$sSql = "
			UPDATE
				`{$sTable}`
			SET
				`frontend_log_id` = 0,
			    `changed` = `changed`
			WHERE
			    `frontend_log_id` IS NULL AND
				`{$sColumn}` = 1
		";

		DB::executeQuery($sSql);

		DB::executeQuery("ALTER TABLE `{$sTable}` DROP `{$sColumn}`");

	}
}
