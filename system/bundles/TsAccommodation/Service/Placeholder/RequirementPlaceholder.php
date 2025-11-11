<?php

namespace TsAccommodation\Service\Placeholder;

class RequirementPlaceholder extends \Ext_TC_Placeholder_Abstract
{

	protected $_aSettings = [
		'variable_name' => 'requirement'
	];

	protected $_aPlaceholders = [
		'requirement_name' => [
			'label' => 'Name',
			'type' => 'field',
			'source' => 'name',
		]
	];

}
