<?php

namespace TsActivities\Service\Placeholder;

class BlockDay extends \Ext_TC_Placeholder_Abstract
{
	protected $_aSettings = [
		'variable_name' => 'oBlockDay'
	];

	protected $_aPlaceholders = [
		'activity_block_day' => array(
			'label' => 'Tag',
			'type' => 'method',
			'source' => 'getDay',
		),
		'activity_block_day_start_time' => array(
			'label' => 'Startzeit',
			'type' => 'field',
			'source' => 'start_time',
			'format' => 'Ext_Thebing_Gui2_Format_Time',
		),
		'activity_block_day_end_time' => array(
			'label' => 'Endzeit',
			'type' => 'field',
			'source' => 'end_time',
			'format' => 'Ext_Thebing_Gui2_Format_Time',
		),
		'activity_block_day_place' => array(
			'label' => 'Ort',
			'type' => 'field',
			'source' => 'place',
		),
		'activity_block_day_companion' => [
			'label' => 'Begleiter',
			'type' => 'method',
			'source' => 'getCompanion',
		],
		'activity_block_day_comment' => array(
			'label' => 'Kommentar',
			'type' => 'field',
			'source' => 'comment',
		),
	];
}
