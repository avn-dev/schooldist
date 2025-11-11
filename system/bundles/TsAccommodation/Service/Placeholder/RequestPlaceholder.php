<?php

namespace TsAccommodation\Service\Placeholder;

class RequestPlaceholder extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oRequest'
	];

	protected $_aPlaceholders = [
		'inquiry_accommodation' => [
			'label' => 'Unterkunftsbuchung',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'inquiry_accommodation',
			'variable_name' => 'oInquiryAccommodation'
		],
		'user_id' => [
			'label' => 'User-ID',
			'source' => 'user_id'
		]
	];

}
