<?php

namespace TsAccommodation\Service\Placeholder;

use TsAccommodation\Entity\Requirement;

class AccommodationRequirementsPlaceholder extends \Ext_TC_Placeholder_Abstract
{

	protected $_aSettings = [
		'variable_name' => 'accommodationRequirements'
	];

	protected $_aPlaceholders = [
		'accommodation' => [
			'label' => 'Unterkunft',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getAccommodation',
			'class' => \Ext_Thebing_Accommodation::class,
			'variable_name' => 'accommodation'
		],
		'requirement_loop' => [
			'label' => 'Voraussetzungen',
			'type' => 'loop',
			'loop' => 'method',
			'source' => 'getRequirements',
			'variable_name' => 'requirements',
			'class' => Requirement::class,
		]
	];

}
