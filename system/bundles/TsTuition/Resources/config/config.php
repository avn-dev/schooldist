<?php

use Core\Service\Hook\AbstractHook;

return [
	'event_manager' => [
		'listen' => [
			\TsTuition\Events\BlockCanceled::class,
			//\TsTuition\Events\LastLevelChange::class,
			\TsTuition\Events\CurrentAllocatedStudents::class,
			\TsTuition\Events\ClassConfirmed::class,
		]
	],

	'external_apps' => [
		\TsTuition\Handler\CourseRenewalApp::APP_NAME => [
			'class' => TsTuition\Handler\CourseRenewalApp::class
		],
		\TsTuition\Handler\HalloAiApp::APP_NAME => [
			'class' => \TsTuition\Handler\HalloAiApp::class
		]
	],

	'hooks' => [
		\Core\Command\Scheduler::HOOK_NAME => [
			'class' => TsTuition\Hook\SchedulerHook::class,
			'interface' => AbstractHook::BACKEND
		]
	],

	'communication' => [
		'recipients' => [
			'teacher' => 'Lehrer'
		],
		'applications' => [
			'tuition_allocation' => \TsTuition\Communication\Application\Allocation::class,
			'tuition_attendance' => \TsTuition\Communication\Application\Attendance::class,
			'placement_test' => \TsTuition\Communication\Application\PlacementTest::class,
			'tuition_teacher' => \TsTuition\Communication\Application\Teacher::class,
			'contract_teacher' => \TsTuition\Communication\Application\TeacherContract::class,
		],
		'flags' => [
			'attendance_warning' => \TsTuition\Communication\Flag\AttendanceWarning::class,
			'teacher_contract_sent' => \TsTuition\Communication\Flag\Teacher\ContractSent::class
		]
	],

	'parallel_processing_mapping' => [
		\TsTuition\Handler\ParallelProcessing\CourseRenewal::TASK_NAME => [
			'class' => TsTuition\Handler\ParallelProcessing\CourseRenewal::class
		],
		'lesson-duration' => [
			'class' => \TsTuition\Handler\ParallelProcessing\LessonDuration::class
		],
		'block-status-flag' => [
			'class' => \TsTuition\Handler\ParallelProcessing\BlockStatusFlag::class
		],
		'attendance-service' => [
			'class' => \TsTuition\Handler\ParallelProcessing\AttendanceService::class
		],
		'block-cancellation' => [
			'class' => \TsTuition\Handler\ParallelProcessing\BlockCancellation::class
		],
		'course-lessons-catch_up' => [
			'class' => \TsTuition\Handler\ParallelProcessing\CourseLessonsCatchUp::class
		],
		'lesson-contingent' => [
			'class' => \TsTuition\Handler\ParallelProcessing\CourseLessonContingent::class
		],
		'course-extension-allocation' => [
			'class' => \TsTuition\Handler\ParallelProcessing\CourseExtensionAllocation::class
		]
	],

	'webpack' => [
		['entry' => 'js/tuition.ts', 'output' => '&', 'config' => 'backend', 'library' => ['name' => '__FIDELO__', 'type' => 'assign-properties']],
		['entry' => 'js/scheduling.ts', 'output' => '&', 'config' => 'backend', 'library' => ['name' => '__FIDELO__', 'type' => 'assign-properties']],
		['entry' => 'scss/progress_report.scss', 'output' => '&', 'config' => 'backend'],
		['entry' => 'scss/scheduler.scss', 'output' => '&', 'config' => 'backend']
	],

	'tailwind' => [
		'content' => [
			'./system/bundles/TsTuition/Resources/views/report/tab_cols.tpl'
		]
	]
];