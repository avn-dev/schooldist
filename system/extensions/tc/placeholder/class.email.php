<?php

class Ext_TC_Placeholder_Email extends Ext_TC_Placeholder_Abstract
{
	protected $_aSettings = [
		'variable_name' => 'oEmail'
	];

	protected $_aPlaceholders = [
		'email' => [
			'label' => 'E-Mail',
			'type' => 'field',
			'source' => 'email'
		]
	];
}
