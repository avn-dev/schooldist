<?php


class Ext_Thebing_System_Checks_ChangeToInnoDB extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Upgrading database engine';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Verifying database structure and changing database engine to InnoDB.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		// Zuordnung von Schüler / Buchung zu Schule prüfen
		$oIdCheck = new Ext_Thebing_System_Checks_SchoolClientId();
		$oIdCheck->executeCheck();

		// Datenbank bereinigen, damit sie kleiner wird
		$oCleanDatabase = new Ext_Thebing_System_Checks_CleanDatabase();
		$oCleanDatabase->executeCheck();

		// Tabellen von MyISAM auf InnoDB umstellen
		$aTables = array(
			"customer_db_1",
			"kolumbus_flex_sections_fields_values",
			"kolumbus_inquiries",
			"kolumbus_inquiries_accommodations",
			"kolumbus_inquiries_additional_documents_relation",
			"kolumbus_inquiries_courses",
			"kolumbus_inquiries_courses_modules",
			"kolumbus_inquiries_courses_structure",
			"kolumbus_inquiries_documents",
			"kolumbus_inquiries_documents_creditnote",
			"kolumbus_inquiries_documents_paymentdocuments",
			"kolumbus_inquiries_documents_versions",
			"kolumbus_inquiries_documents_versions_fields",
			"kolumbus_inquiries_documents_versions_items",
			"kolumbus_inquiries_documents_versions_items_changes",
			"kolumbus_inquiries_group_documents",
			"kolumbus_inquiries_group_documents_percent",
			"kolumbus_inquiries_group_flags",
			"kolumbus_inquiries_holidays",
			"kolumbus_inquiries_holidays_accommodationsave",
			"kolumbus_inquiries_holidays_coursesave",
			"kolumbus_inquiries_holidays_splitting",
			"kolumbus_inquiries_insurances",
			"kolumbus_inquiries_payments",
			"kolumbus_inquiries_payments_agencypayments",
			"kolumbus_inquiries_payments_documents",
			"kolumbus_inquiries_payments_items",
			"kolumbus_inquiries_payments_overpayment",
			"kolumbus_inquiries_payments_overpayment_transaction",
			"kolumbus_inquiries_payments_reminders",
			"kolumbus_inquiries_payments_types",
			"kolumbus_inquiries_positions_specials",
			"kolumbus_inquiries_transfers",
			"kolumbus_inquiries_transfers_provider_request",
			"kolumbus_roomsharing",
			"kolumbus_tuition_blocks_inquiries_courses"
		);

		foreach($aTables as $sTable) {

			Ext_Thebing_Util::backupTable($sTable, true);

			$sSql = "ALTER TABLE #table ENGINE = InnoDB;";
			$aSql = array('table'=>$sTable);
			DB::executePreparedQuery($sSql, $aSql);

		}

		return true;

	}

}

