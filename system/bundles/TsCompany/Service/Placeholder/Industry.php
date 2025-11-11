<?php

namespace TsCompany\Service\Placeholder;

class Industry extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oCompanyIndustry'
	];

	protected $_aPlaceholders = [
		'industry_name' => [
			'label' => 'Bezeichnung',
			'type' => 'method',
			'source' => 'getName'
		],
	];

}
