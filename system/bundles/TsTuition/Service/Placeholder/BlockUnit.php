<?php

namespace TsTuition\Service\Placeholder;

class BlockUnit extends \Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = [
		'variable_name' => 'oTuitionBlockUnit'
	];

	protected $_aPlaceholders = [
		'tuition_block_unit_weekday' => [
			'label' => 'Wochentag',
			'type' => 'field',
			'source' => 'day',
			'format' => \Tc\Gui2\Format\Weekday::class
		],
		'tuition_block_unit_comment' => [
			'label' => 'Kommentar',
			'type' => 'field',
			'source' => 'comment'
		],
		'tuition_block_unit_state_comment' => [
			'label' => 'Status-Kommentar',
			'type' => 'field',
			'source' => 'state_comment'
		],
		'tuition_block_unit_date' => [
			'label' => 'Datum',
			'type' => 'method',
			'source' => 'getStartDate',
			'format' => \Ext_Thebing_Gui2_Format_Date::class
		],
		'tuition_block_unit_start_time' => [
			'label' => 'Uhrzeit: Von',
			'type' => 'method',
			'source' => 'getStartDate',
			'format' => \Ext_Thebing_Gui2_Format_Time::class
		],
		'tuition_block_unit_end_time' => [
			'label' => 'Uhrzeit: Bis',
			'type' => 'method',
			'source' => 'getEndDate',
			'format' => \Ext_Thebing_Gui2_Format_Time::class
		]
	];
	
}
