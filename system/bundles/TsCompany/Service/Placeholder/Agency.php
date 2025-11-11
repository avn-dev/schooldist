<?php

namespace TsCompany\Service\Placeholder;

class Agency extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oAgency'
	];

	protected $_aPlaceholders = [
		'agency_name' => [
			'label' => 'Name',
			'type' => 'method',
			'source' => 'getName',
			'method_parameter' => true
		],
		'agency_short_name' => [
			'label' => 'Abkürzung',
			'type' => 'method',
			'source' => 'getName',
			'method_parameter' => false
		],
		'agency_tracking_code' => [
			'label' => 'Tracking-Code',
			'source' => 'tracking_key',
		],
	];

}
