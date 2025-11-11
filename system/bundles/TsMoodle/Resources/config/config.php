<?php

use Core\Service\Hook\AbstractHook;

return [

	'hooks' => [
		// Schüler aktualisieren, falls vorhanden
		'ts_inquiry_save' => [
			'class' => TsMoodle\Hook\InquirySaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
		// Kategorie abgleichen
		'ts_course_save' => [
			'class' => TsMoodle\Hook\CourseSaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
		// Klasse abgleichen
		'ts_class_save' => [
			'class' => TsMoodle\Hook\ClassSaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
		// Klassenzuweisung abgleichen (Enrolment)
		'ts_class_assignment_save' => [
			'class' => TsMoodle\Hook\ClassAssignmentSaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
		// Klassenzuweisung deaktivieren (Unenrolment)
		'ts_class_assignment_deactivate' => [
			'class' => TsMoodle\Hook\ClassAssignmentDeactivateHook::class,
			'interface' => AbstractHook::BACKEND
		]
	],

	'external_apps' => [
		TsMoodle\Handler\ExternalApp::APP_NAME => [
			'class' => TsMoodle\Handler\ExternalApp::class
		]
	],
	
	'parallel_processing_mapping' => [
		'sync-inquiry' => [
			'class' => TsMoodle\Handler\ParallelProcessing\SyncInquiry::class
		],
		'sync-course' => [
			'class' => TsMoodle\Handler\ParallelProcessing\SyncCourse::class
		],
		'sync-class' => [
			'class' => TsMoodle\Handler\ParallelProcessing\SyncClass::class
		],
		'sync-class-assignment' => [
			'class' => TsMoodle\Handler\ParallelProcessing\SyncClassAssignment::class
		],
		
	]			

];
