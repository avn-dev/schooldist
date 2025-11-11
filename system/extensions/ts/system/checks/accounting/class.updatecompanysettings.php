<?php

class Ext_TS_System_Checks_Accounting_UpdateCompanySettings extends GlobalChecks {

	public function getTitle() {
		return 'Update company settings';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {
		
		#always_create_claim_debt -> create_claim_debt
		$aQueries = [
			"ALTER TABLE `ts_companies` CHANGE `always_create_claim_debt` `create_claim_debt` TINYINT(1) NOT NULL DEFAULT '1'",
			"ALTER TABLE `ts_companies` ADD `deferred_income` TINYINT NOT NULL DEFAULT '1'",
			"ALTER TABLE `ts_companies` ADD `accounting_records` ENUM('deferred_income','single','line_item','') NOT NULL DEFAULT 'deferred_income'",
			"UPDATE `ts_companies` SET `interface` = 'quickbooks', `accounting_records` = 'single' WHERE `interface` = 'quickbooks_basic'",
			"UPDATE `ts_companies` SET `interface` = 'sage', `accounting_records` = 'single' WHERE `interface` = 'sage_basic'",
			"UPDATE `ts_companies` SET `interface` = 'universal' WHERE `interface` = 'sage'",
			"UPDATE `ts_companies` SET `create_claim_debt` = 1 WHERE `accounting_type` = 'double'"
		];
		
		foreach($aQueries as $sQuery) {
			try {
				DB::executeQuery($sQuery);
			} catch (Exception $ex) {
				__pout($ex->getMessage());
			}
		}

		\TsAccounting\Entity\Company::deleteTableCache();

		return true;
	}

}
