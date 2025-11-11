<?php

namespace TsAccommodation\Service;

class AccommodationPlaceholder extends \Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = [
		'variable_name' => 'accommodation'
	];

	protected $_aFlexibleFieldsSections = [
		'accommodation_providers_bank',
		'accommodation_providers_general',
		'accommodation_providers_info'
	];

	protected $_aPlaceholders = [
		'accommodation_name' => [
			'label' => 'Name',
			'source' => 'ext_33'
		],
		'accommodation_reset_password_link' => [
			'label' => 'Passwort-Zurücksetzen-Link',
			'type' => 'method',
			'source' => 'getResetPasswordLink'
		],
		'accommodation_way_to_school' => [
			'label' => 'Wegbeschreibung',
			'type' => 'method',
			'source' => 'getWayDescription',
			'pass_language' => true
		],
		'accommodation_family_description' => [
			'label' => 'Familienbeschreibung',
			'type' => 'method',
			'source' => 'getFamilyDescription',
			'pass_language' => true
		],
		'accommodation_residential_area' => [
			'label' => 'Wohnumgebung',
			'source' => 'ext_50'
		]
	];



}
