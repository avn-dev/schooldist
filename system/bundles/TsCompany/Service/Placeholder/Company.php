<?php

namespace TsCompany\Service\Placeholder;

class Company extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oCompany'
	];

	protected $_aPlaceholders = [
		'company_name' => [
			'label' => 'Name',
			'type' => 'method',
			'source' => 'getName',
			'method_parameter' => true
		],
		'company_short_name' => [
			'label' => 'Name',
			'type' => 'method',
			'source' => 'getName',
			'method_parameter' => false
		],
	];

}
