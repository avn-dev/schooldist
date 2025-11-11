<?php

namespace TsActivities\Service\Placeholder;

class BlockTraveller extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oBlockTraveller'
	];

	protected $_aPlaceholders = [
		'activity_assignment_start_date' => [
			'label' => 'Zuweisung - Startdatum',
			'type' => 'method',
			'source' => 'getStartDate',
			'format' => 'Ext_Thebing_Gui2_Format_Date'
		],
		'activity_block' => [
			'label' => 'Block',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'block',
			'variable_name' => 'oActivityBlock',
			'class' => \TsActivities\Entity\Activity\Block::class
		],
//		'activity_name' => [
//			'label' => 'Aktivität',
//			'type' => 'method',
//			'source' => 'getActivity',
//			'variable_name' => 'iActivityId'
//		],
		'contact' => [
			'label' => 'Schüler',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'contact',
		]
	];

}
