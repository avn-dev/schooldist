<?php

use TsCompany\Entity\AbstractCompany;

/**
 * Generiert die initiale Struktur für Firmen
 */
class Ext_TS_System_Checks_Company_InitialStructure extends \GlobalChecks {

	public function getTitle() {
		return "Co-Op";
	}

	public function getDescription() {
		return "Initial migration for Co-Op-Structure";
	}

	public function executeCheck() {

		if (!\DB::getDefaultConnection()->checkTable('kolumbus_agencies')) {
			// Check bereits durchgelaufen
			return true;
		}

		\Util::backupTable('wdbasic_attributes');

		$rename = [
			'ts_companies' => 'ts_accounting_companies',
			'ts_companies_colums_export' => 'ts_accounting_companies_columns_export',
			'ts_companies_combinations' => 'ts_accounting_companies_combinations',
			'ts_companies_combinations_to_inboxes' => 'ts_accounting_companies_combinations_to_inboxes',
			'ts_companies_combinations_to_schools' => 'ts_accounting_companies_combinations_to_schools',
			'ts_company_account_allocations' => 'ts_accounting_companies_account_allocations',
			'ts_company_template_receipt_text' => 'ts_accounting_company_template_receipt_text',
			'ts_company_template_receipt_text_to_companies' => 'ts_accounting_company_template_receipt_text_to_companies',
			'ts_company_template_receipt_text_to_inboxes' => 'ts_accounting_company_template_receipt_text_to_inboxes',
			'ts_company_template_receipt_text_to_schools' => 'ts_accounting_company_template_receipt_text_to_schools',
			'kolumbus_agencies' => 'ts_companies',
			'kolumbus_agencies_uploads' => 'ts_companies_uploads',
			'kolumbus_agency_addresses' => 'ts_companies_addresses',
			'kolumbus_agency_comments' => 'ts_companies_comments',
			'ts_agencies_contacts' => 'ts_companies_contacts',
			'ts_agencies_numbers' => 'ts_companies_numbers',
		];

		try {

			\DB::executeQuery("INSERT INTO `system_elements` (`id`, `title`, `description`, `element`, `category`, `file`, `version`, `parent`, `image`, `documentation`, `template`, `sql`, `administrable`, `include_backend`, `include_frontend`, `include_mode`, `active`) VALUES (NULL, 'TsCompany', '', 'bundle', 'Fidelo', 'TsCompany', '0.010', '', '', '', '', '', '0', '1', '0', '0', '1') ");

			// Transaktionen bringen hier nichts

			foreach ($rename as $from => $to) {
				// Sicherheitshalber mal ein Backup anlegen (weil unten auch noch Spalten umbenannt werden)
				\Util::backupTable($from);
				// Tabelle umbenennen
				\DB::executePreparedQuery('RENAME TABLE #from TO #to', ['from' => $from, 'to' => $to]);
				// WDBasic-Attributes umschreiben
				\DB::updateData('wdbasic_attributes', ['entity' => $to], ['entity' => $from]);
			}

			\DB::executeQuery("ALTER TABLE `ts_companies_uploads` CHANGE `agency_id` `company_id` INT(11) NOT NULL");
			\DB::executeQuery("ALTER TABLE `ts_companies_addresses` CHANGE `agency_id` `company_id` INT(11) NOT NULL");
			\DB::executeQuery("ALTER TABLE `ts_companies_comments` CHANGE `agency_id` `company_id` INT(11) NOT NULL, CHANGE `agency_contact_id` `company_contact_id` INT(11) NOT NULL");
			\DB::executeQuery("ALTER TABLE `ts_companies_contacts` CHANGE `agency_id` `company_id` INT(11) NOT NULL DEFAULT '0'");
			\DB::executeQuery("ALTER TABLE `ts_companies_numbers` CHANGE `agency_id` `company_id` MEDIUMINT(8) UNSIGNED NOT NULL");

			// "type" als BIT-Operator um in Zukunft flexibler zu sein, wahrscheinlich werden auch noch die Sponsoren auf ts_companies umgestellt.
			\DB::executeQuery("ALTER TABLE `ts_companies` ADD `type` BIT(2) NULL DEFAULT NULL COMMENT 'BIT: 1=company, 2=agency' AFTER `idSchool`, ADD INDEX (`type`)");

			// Firmen initial auf "Agentur" stellen
			\DB::executeQuery("UPDATE `ts_companies` SET `type` = 0 | " . AbstractCompany::TYPE_AGENCY . " WHERE `type` IS NULL");

		} catch (\Throwable $e) {
			return false;
		}

		return true;
	}

}
