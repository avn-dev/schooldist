<?php

namespace Tc\Service\Placeholder\EventManager;

class EventEntityPlaceholders extends \Ext_TC_Placeholder_Abstract
{
	protected $_aSettings = [
		'variable_name' => 'oEventManagement'
	];

	protected $_aPlaceholders = [
		'event_name' => [
			'label' => 'Event',
			'type' => 'field',
			'source' => 'name',
		]
	];
}