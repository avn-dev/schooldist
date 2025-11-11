<?php

namespace Ts\Service\Placeholder;

class School extends \Ext_TC_Placeholder_Abstract
{

	protected $_aSettings = [
		'variable_name' => 'school'
	];

	protected $_aPlaceholders = [
		'school_name' => [
			'label' => 'Name',
			'type' => 'field',
			'source' => 'ext_1'
		],
	];

}