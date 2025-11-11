<?php

namespace TsTuition\Service\Placeholder;

use Tc\Gui2\Format\JoinArray;

class Block extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'tuitionBlock'
	];

	protected $_aPlaceholders = [
		'tuition_block_dates' => [
			'label' => 'Daten',
			'type' => 'method',
			'source' => 'getDates',
			'format' => JoinArray::class,
		],
		'tuition_block_weekdays' => [
			'label' => 'Wochentage',
			'type' => 'method',
			'source' => 'getDays',
			'format' => JoinArray::class,
			'pass_language' => true
		],
		'tuition_block_times' => [
			'label' => 'Zeiten',
			'type' => 'method',
			'source' => 'getFormattedTimes',
			'format' => JoinArray::class,
			'format_parameter' => ['-']
		],
		'tuition_block_level' => [
			'label' => 'Niveau',
			'type' => 'field',
			'source' => 'level_id',
			'format' => 'Ext_Thebing_Gui2_Format_Inquiry_Tuition_Level'
		],
		'tuition_block_teacher' => [
			'label' => 'Lehrer',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'teacher',
			'variable_name' => 'blockTeacher'
		],
		'tuition_block_class' => [
			'label' => 'Klasse',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'class',
			'variable_name' => 'blockClass'
		],
		'tuition_block_classroom_loop' => [
			'label' => 'Klassenzimmer',
			'type' => 'loop',
			'loop' => 'join_table',
			'source' => 'rooms',
			'variable_name' => 'blockRooms'
		],
	];

}