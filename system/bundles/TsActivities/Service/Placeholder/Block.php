<?php

namespace TsActivities\Service\Placeholder;

class Block extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oBlock'
	];

	protected $_aPlaceholders = [
		'activity_block_days_loop' => [
			'label' => 'Tage',
			'type' => 'loop',
			'loop' => 'child',
			'child' => 'joined_object',
			'source' => 'days',
			'variable_name' => 'oActivityBlockDay',
			'class' => \TsActivities\Entity\Activity\BlockDay::class
		],
		'activity_block_name' => array(
			'label' => 'Block',
			'type' => 'field',
			'source' => 'name',
		),
		'activity_block_start_date' => array(
			'label' => 'Startdatum',
			'type' => 'method',
			'source' => 'getStartDate',
			'format' => 'Ext_Thebing_Gui2_Format_Date'
		),
//		'block_date' => [
//			'label' => 'Blockdatum',
//			'type' => 'method',
//			'source' => 'getBlockDate',
//		],
		// Das macht so keinen Sinn, weil das alles pro Tag ist
//		'duration' => [
//			'label' => 'Blockdauer',
//			'type' => 'method',
//			'source' => 'getDuration',
//		],
//		'description' => [
//			'label' => 'Blockbeschreibung',
//			'type' => 'method',
//			'source' => 'getDescription',
//		],
//		'date_start' => [
//			'label' => 'Blockstartdatum',
//			'type' => 'method',
//			'source' => 'getStartDate',
//		],
//		'weekday' => [
//			'label' => 'Wochentag',
//			'type' => 'method',
//			'source' => 'getWeekday',
//		],
//		'time_start' => [
//			'label' => 'Startzeit',
//			'type' => 'method',
//			'source' => 'getStartTime',
//		],
//		'time_end' => [
//			'label' => 'Endzeit',
//			'type' => 'method',
//			'source' => 'getEndTime',
//		]
	];

}