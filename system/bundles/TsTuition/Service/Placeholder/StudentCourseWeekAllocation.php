<?php

namespace TsTuition\Service\Placeholder;

class StudentCourseWeekAllocation extends \Ext_TC_Placeholder_Abstract
{
	protected $_aSettings = [
		'variable_name' => 'studentWeekCourseAllocation'
	];

	protected $_aPlaceholders = [
		'week' => [
			'label' => 'Woche',
			'type' => 'method',
			'source' => 'getWeek',
			'format' => \Ext_Thebing_Gui2_Format_Date::class,
		],
		'program_service' => [
			'label' => 'Kurs',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getCourse',
			'class' => \Ext_Thebing_Tuition_Course::class,
			'variable_name' => 'allocationProgramService',
		],
		'journey_course' => [
			'label' => 'Kursbuchung',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getJourneyCourse',
			'class' => \Ext_TS_Inquiry_Journey_Course::class,
			'variable_name' => 'allocationJourneyCourse',
		]
	];
}