<?php

namespace Ts\Service\Placeholder\Booking;

class Matching extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oMatching'
	];

	protected $_aPlaceholders = [
		'allergies' => [
			'label' => 'Allergien',
			'source' => 'acc_allergies'
		]
	];
	
}
