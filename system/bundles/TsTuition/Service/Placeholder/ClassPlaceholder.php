<?php

namespace TsTuition\Service\Placeholder;

/**
 * Die Platzhalterklasse kann man nicht "Class" nennen...
 */
class ClassPlaceholder extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'tuitionClass'
	];

	protected $_aPlaceholders = [
		'tuition_class_name' => [
			'label' => 'Name',
			'type' => 'field',
			'source' => 'name',
			'variable_name' => 'className'
		],
	];

}