<?php

namespace TsTuition\Service\Placeholder;

class CourseLanguage extends \Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = [
		'variable_name' => 'oCourseLanguage'
	];

	protected $_aFlexibleFieldsSections = [
		'tuition_course_languages'
	];
	
	protected $_aPlaceholders = [
		'course_language_name' => [
			'label' => 'Name',
			'type' => 'method',
			'source' => 'getName',
			'pass_language' => true
		],
	];
	
}
