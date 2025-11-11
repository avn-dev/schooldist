<?php

class Ext_Thebing_Tuition_Course_Placeholder extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = array(
		'variable_name' => 'course'
	);

	protected $_aPlaceholders = array(
		'course_name' => array(
			'label' => 'Name',
			'type' => 'method',
			'source' => 'getName',
			'pass_language' => true
		),
		'course_abbreviation' => array(
			'label' => 'Abkürzung',
			'type' => 'method',
			'source' => 'getShortName'
		),
		'course_frontend_name' => array(
			'label' => 'Frontend-Name',
			'type' => 'method',
			'source' => 'getFrontendName',
			'pass_language' => true
		),
		'superordinate_course' => [
			'label' => 'Übergeordneter Kurs',
			'type' => 'parent',		
			'parent' => 'joined_object',
			'source' => 'superordinate_course',
			'variable_name' => 'oSuperordinateCourse'
		]
	);
	
}
