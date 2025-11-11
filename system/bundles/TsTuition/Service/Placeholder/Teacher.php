<?php

namespace TsTuition\Service\Placeholder;

class Teacher extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'tuitionTeacher'
	];

	protected $_aPlaceholders = [
		'tuition_teacher_firstname' => [
			'label' => 'Vorname',
			'type' => 'field',
			'source' => 'firstname',
			'variable_name' => 'teacherFirstname'
		],
		'tuition_teacher_lastname' => [
			'label' => 'Nachname',
			'type' => 'field',
			'source' => 'lastname',
			'variable_name' => 'teacherLastname'
		],
	];

}