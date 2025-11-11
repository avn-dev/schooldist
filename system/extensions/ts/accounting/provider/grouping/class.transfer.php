<?php

use Communication\Interfaces\Model\CommunicationSubObject;

class Ext_TS_Accounting_Provider_Grouping_Transfer extends Ext_TS_Accounting_Provider_Grouping_Abstract {

	protected $_sTable = 'ts_transfers_payments_groupings';
	protected $_sTableAlias = 'ts_tpg';

	protected $_aJoinedObjects = array(
		'payments' => array(
			'class' => 'Ext_Thebing_Transfer_Payment',
			'key' => 'grouping_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		)
	);

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		$aSqlParts['select'] .= ",
			`ktco`.`name` `transfer_company_name`,
			`ktco`.`bank_account_holder` `transfer_company_bank_account_holder`,
			`ktco`.`bank_account_number` `transfer_company_bank_account_number`,
			`ktco`.`bank_code` `transfer_company_bank_account_code`,
			`ktco`.`bank_name` `transfer_company_bank_name`,
			`ktco`.`bank_address` `transfer_company_bank_address`,
			`cdb4`.`ext_33` `accommodation_provider_name`,
			`cdb4`.`ext_68` `accommodation_provider_bank_account_holder`,
			`cdb4`.`ext_70` `accommodation_provider_bank_account_number`,
			`cdb4`.`ext_71` `accommodation_provider_bank_account_code`,
			`cdb4`.`ext_69` `accommodation_provider_bank_name`,
			`cdb4`.`ext_72` `accommodation_provider_bank_address`
		";

		// Joinen für Suche
		$aSqlParts['select'] .= Ext_Thebing_Transfer_Payment::getSqlPart('select');


		$aSqlParts['from'] .= " LEFT JOIN
			`kolumbus_transfers_payments` `ktrpa` ON
				`ktrpa`.`grouping_id` = `ts_tpg`.`id`
		";

		// Joinen für Suche
		$aSqlParts['from'] .= Ext_Thebing_Transfer_Payment::getSqlPart('from');

		$aSqlParts['from'] .= " LEFT JOIN
			`kolumbus_companies` `ktco` ON
				`ts_tpg`.`provider_type` = 'provider' AND
				`ktco`.`id` = `ts_tpg`.`provider_id` LEFT JOIN
			`customer_db_4` `cdb4` ON
				`ts_tpg`.`provider_type` = 'accommodation' AND
				`cdb4`.`id` = `ts_tpg`.`provider_id`
		";

		$aSqlParts['where'] .= "
			AND `ts_tpg`.`school_id` = {$oSchool->id}
		";

		$aSqlParts['groupby'] = "
			`ts_tpg`.`id`
		";

	}

	public function getOldPlaceholderObject(SmartyWrapper $oSmarty=null) {
		$oProvider = $this->getItem();

		if($this->provider_type === 'accommodation') {
			$oPlaceholder = new Ext_TS_Accounting_Provider_Grouping_Accommodation_Placeholder($oProvider, $this);
		} else {
			$oPlaceholder = new Ext_TS_Accounting_Provider_Grouping_Transfer_Placeholder($oProvider, $this);
		}

		return $oPlaceholder;
	}

	public function getItem() {
		return $this->getProvider();
	}

	public function getType() {
		return 'transfer';
	}

	/**
	 * Provider dieser Gruppierung
	 *
	 * @return Ext_Thebing_Pickup_Company|Ext_Thebing_Accommodation
	 */
	public function getProvider() {
		if($this->provider_type === 'accommodation') {
			return Ext_Thebing_Accommodation::getInstance($this->provider_id);
		} else {
			return Ext_Thebing_Pickup_Company::getInstance($this->provider_id);
		}
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsAccounting\Communication\Application\TransferPayments::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return $l10n->translate('Transferanbieterzahlung');
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		if($this->provider_type === 'accommodation') {
			$firstSchoolId = \Illuminate\Support\Arr::first($this->getProvider()->schools);
			return Ext_Thebing_School::getInstance($firstSchoolId);
		} else {
			return $this->getProvider()->getSchool();
		}
	}
}