<?php

namespace TsAccounting\Service\Placeholder;

class Company extends \Ext_TC_Placeholder_Abstract
{

	protected $_aSettings = [
		'variable_name' => 'accountingCompany'
	];

	protected $_aFlexibleFieldsSections = [
		'accounting_companies_options'
	];

	protected $_aPlaceholders = [
		'accounting_company_name' => [
			'label' => 'Name',
			'source' => 'name'
		],
	];

}
