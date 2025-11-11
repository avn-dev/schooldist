<?php

namespace TsTuition\Service\Placeholder;

class Classroom extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'tuitionClassroom'
	];

	protected $_aPlaceholders = [
		'tuition_classroom_name' => [
			'label' => 'Name',
			'type' => 'field',
			'source' => 'name',
			'variable_name' => 'classroomName'
		],
	];

}