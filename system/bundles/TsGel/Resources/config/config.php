<?php

use Core\Service\Hook\AbstractHook;

return [

    'external_apps' => [
        TsGel\Handler\ExternalApp::APP_NAME => [
            'class' => TsGel\Handler\ExternalApp::class
        ]
    ],

    'hooks' => [
		'ts_inquiry_save' => [
			'class' => TsGel\Hook\InquirySaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
		// Klassenzuweisung abgleichen (Enrolment)
		'ts_class_assignment_save' => [
			'class' => TsGel\Hook\ClassAssignmentSaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
		// Klassenzuweisung deaktivieren (Unenrolment)
		'ts_class_assignment_deactivate' => [
			'class' => TsGel\Hook\ClassAssignmentDeactivateHook::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_tuition_attendance_save' => [
			'class' => TsGel\Hook\TuitionAttendanceSaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_school_tuition_block_save' => [
			'class' => TsGel\Hook\TuitionBlockSaveHook::class,
			'interface' => AbstractHook::BACKEND
		]
    ],

	'parallel_processing_mapping' => [
		'api-request' => [
			'class' => TsGel\Handler\ParallelProcessing\ApiRequest::class
		]
	],

	'commands' => [
		\TsGel\Commands\SendBooking::class,
		\TsGel\Commands\SendAllBookings::class,
	]

];
