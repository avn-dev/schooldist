<?php

class Ext_Thebing_Examination_Placeholder_Smarty extends Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oExamination'
	];

	protected $_aPlaceholders = [
		'examination_from' => [
			'label' => 'Von',
			'type' => 'method',
			'source' => 'getPlaceholderValue',
			'method_parameter' => ['examination_from'],
			'format' => 'Ext_Thebing_Gui2_Format_Date'
		],
		'examination_until' => [
			'label' => 'Bis',
			'type' => 'method',
			'source' => 'getPlaceholderValue',
			'method_parameter' => ['examination_until'],
			'format' => 'Ext_Thebing_Gui2_Format_Date'
		],
		'examination_score' => [
			'label' => 'Punkte',
			'type' => 'method',
			'source' => 'getPlaceholderValue',
			'method_parameter' => ['examination_score']
		],
		'examination_class_names' => [
			'label' => 'Namen aller Klassen des Kurses (während des Prüfungszeitraums)',
			'type' => 'method',
			'source' => 'getPlaceholderValue',
			'method_parameter' => ['examination_class_names']
		],
		'examination_attendance_notes' => [
			'label' => 'Kommentare aller Anwesenheitseinträge des Kurses (während des Prüfungszeitraums)',
			'type' => 'method',
			'source' => 'getPlaceholderValue',
			'method_parameter' => ['examination_attendance_notes']
		]
	];

}
