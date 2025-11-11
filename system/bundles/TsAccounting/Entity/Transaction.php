<?php

namespace TsAccounting\Entity;

class Transaction extends \Ext_Thebing_Basic {
	
	protected $_sTable = 'ts_accounts_transactions';
	protected $_sTableAlias = 'ts_at';

	/**
	 * @param type $aSqlParts
	 * @param type $sView
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		if($sView == 'transactions') {
			
			
			
		} else {

			$aSqlParts['select'] .= ", 
				SUM(IF(`ts_at`.`type` != 'proforma', `ts_at`.`amount`, 0)) `accounting_balance`,
				SUM(`ts_at`.`amount`) `ledger_balance`, 
				SUM(IF(`ts_at`.`type` = 'invoice', `ts_at`.`amount`, 0)) `amount_invoices`,
				SUM(IF(`ts_at`.`type` = 'proforma', `ts_at`.`amount`, 0)) `amount_proformas`,
				SUM(IF(`ts_at`.`type` = 'payment', `ts_at`.`amount`, 0)) `amount_payments`,
				COALESCE(CONCAT(`tc_c`.`lastname`, ', ', `tc_c`.`firstname`), `kg`.`name`, `ka`.`ext_1`, `ts_s`.`name`) `account_name`, 
				COALESCE(`tc_cn`.`number`, `kg`.`number`, `ts_an`.`number`, `ts_s`.`id`) `account_number`
				 ";

			$aSqlParts['from'] .= " LEFT JOIN
				`tc_contacts` `tc_c` ON
					`ts_at`.`account_type` = 'contact' AND
					`tc_c`.`id` = `ts_at`.`account_id` LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_c`.`id` = `tc_cn`.`contact_id` LEFT JOIN
				`kolumbus_groups` `kg` ON
					`ts_at`.`account_type` = 'group' AND
					`kg`.`id` = `ts_at`.`account_id` LEFT JOIN
				`ts_companies` `ka` ON
					`ts_at`.`account_type` = 'agency' AND
					`ka`.`id` = `ts_at`.`account_id` LEFT JOIN
				`ts_companies_numbers` `ts_an` ON
					`ka`.`id` = `ts_an`.`company_id` LEFT JOIN
				`ts_sponsors` `ts_s` ON
					`ts_at`.`account_type` = 'sponsor' AND
					`ts_s`.`id` = `ts_at`.`account_id`
			";
		
			$aSqlParts['groupby'] .= '`ts_at`.`account_type`, `ts_at`.`account_id`';

		}

	}
	
}
