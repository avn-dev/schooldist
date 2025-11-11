<?php

use Core\Service\Hook\AbstractHook;

return [

    'external_apps' => [
        TsIvvy\Handler\ExternalApp::APP_NAME => [
            'class' => TsIvvy\Handler\ExternalApp::class
        ]
    ],

	'hooks' => [
		'ts_school_tuition_block_save' => [
			'class' => TsIvvy\Hook\TuitionBlockSaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
		'ts_school_tuition_block_delete' => [
			'class' => TsIvvy\Hook\TuitionBlockSaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
		// Parkplätze
		'ts_matching_save_allocation' => [
			'class' => TsIvvy\Hook\ParkingAllocationSaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
		// Cronjob
		'tc_cronjobs_5minutes_execute' => [
			'class' => TsIvvy\Hook\CronjobHook::class,
			'interface' => AbstractHook::BACKEND
		],
	],

	'parallel_processing_mapping' => [
		'sync-entity' => [
			'class' => TsIvvy\Handler\ParallelProcessing\SyncEntity::class
		],
		'sync-timeframe' => [
			'class' => TsIvvy\Handler\ParallelProcessing\SyncTimeframe::class
		]
	],

    'commands' => [
        \TsIvvy\Commands\ApiPing::class,
        \TsIvvy\Commands\ApiSync::class,
        \TsIvvy\Commands\SyncClass::class,
    ]

];
