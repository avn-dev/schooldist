<?php

namespace Ts\Service\Placeholder\Booking;

class Group extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oGroup'
	];

	protected $_aPlaceholders = [
		'group_name' => [
			'label' => 'Name',
			'type' => 'field',
			'source' => 'name'
		],
	];

}
