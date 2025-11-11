<?php

class Ext_Thebing_School_Tuition_Allocation_Placeholder extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = [
		'variable_name' => 'oTuitionAllocation'
	];
	
	protected $_aFlexibleFieldsSections = [
		'tuition_attendance_register'
	];
	
	protected $_aPlaceholders = [
		'tuition_block_course_week_status' => [
			'label' => 'Aktueller Status der Kurswoche',
			'type' => 'method',
			'source' => 'getTuitionCourseWeekStatus'
		],
		'tuition_block' => [
			'label' => 'Block',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'block',
			'variable_name' => 'oAllocationBlock'
		],
		'inquiry_course' => [
			'label' => 'Kursbuchung',
			'type' => 'parent',
			'parent' => 'joined_object',
			'source' => 'inquiry_course',
			'variable_name' => 'oInquiryCourse'
		]
	];
	
}
