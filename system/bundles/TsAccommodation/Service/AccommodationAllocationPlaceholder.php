<?php

namespace TsAccommodation\Service;

class AccommodationAllocationPlaceholder extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'accommodationAllocation'
	];

	protected $_aPlaceholders = [
		'inquiry_accommodation' => [
			'label' => 'Unterkunftsbuchung',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getAccommodationJourney',
			'class' => \Ext_TS_Inquiry_Journey_Accommodation::class,
			'variable_name' => 'accommodationJourney'
		],
	];

}
