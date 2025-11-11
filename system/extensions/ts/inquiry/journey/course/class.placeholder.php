<?php

class Ext_TS_Inquiry_Journey_Course_Placeholder extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = [
		'variable_name' => 'journeyCourse'
	];

	protected $_aFlexibleFieldsSections = [
		'student_record_course'
	];

	public function getPlaceholders()
	{
		$journey = $this->_oWDBasic->getJourney();
		$isBooking = ($journey && $journey->type & \Ext_TS_Inquiry_Journey::TYPE_BOOKING) ? true : false;

		return [
			'course' => [
				'label' => 'Kurs',
				'type' => 'parent',
				'parent' => 'joined_object',
				'source' => 'course',
				'variable_name' => 'oCourse',
				'class' => Ext_Thebing_Tuition_Course::class
			],
			'course_language' => [
				'label' => 'Kurssprache',
				'type' => 'parent',
				'parent' => 'joined_object',
				'source' => 'course_language'
			],
			'link_placementtest' => [
				'label' => 'Link zum Einstufungstest',
				'type' => 'method',
				'source' => 'getPlacementtestLink',
				'invisible' => !$isBooking,
				'only_final_output' => true
			],
			'link_placementtest_halloai' => [
				'label' => 'Link zum Einstufungstest',
				'type' => 'method',
				'source' => 'getHalloAiPlacementtestLink',
				'invisible' => !$isBooking || !\TcExternalApps\Service\AppService::hasApp(\TsTuition\Handler\HalloAiApp::APP_NAME),
				'only_final_output' => true
			],
			'course_lessons' => [
				'label' => 'Lektionen',
				'type' => 'method',
				'source' => 'getUnits',
			],
			'course_start' => [
				'label' => 'Beginn',
				'type' => 'field',
				'source' => 'from',
				'format' => 'Ext_Thebing_Gui2_Format_Date',
			],
			'course_end' => [
				'label' => 'Ende',
				'type' => 'field',
				'source' => 'until',
				'format' => 'Ext_Thebing_Gui2_Format_Date',
			],
			'course_comment' => [
				'label' => 'Kommentar',
				'type' => 'field',
				'source' => 'comment'
			],
			'course_duration_weeks' => [
				'label' => 'Wochen',
				'type' => 'field',
				'source' => 'weeks'
			],
			'course_initial_level' => [
				'label' => 'Startniveau',
				'type' => 'method',
				'source' => 'getTuitionStartLevel',
				'invisible' => !$isBooking
			],
			'tuition_block_loop' => [
				'label' => 'Kursblöcke',
				'type' => 'loop',
				'loop' => 'joined_object',
				'source' => 'tuition_blocks',
				'variable_name' => 'journeyCourseTuitionAllocation',
				'exclude_placeholders' => ['inquiry_course'],
				'invisible' => !$isBooking
			],
			'inquiry' => [
				'label' => 'Buchung',
				'type' => 'parent',
				'parent' => 'method',
				'source' => 'getInquiry',
				'variable_name' => 'oInquiry'
			]
		];
	}

}
