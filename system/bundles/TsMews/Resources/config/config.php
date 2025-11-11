<?php

use Core\Service\Hook\AbstractHook;

return [

    'external_apps' => [
        TsMews\Handler\ExternalApp::APP_NAME => [
            'class' => TsMews\Handler\ExternalApp::class
        ]
    ],

    'hooks' => [
        // Schüler aktualisieren, falls vorhanden
        'ts_inquiry_save' => [
            'class' => TsMews\Hook\InquirySaveHook::class,
            'interface' => AbstractHook::BACKEND
        ],
        // Prüfen ob Zuweisung möglich
        'ts_matching_check_allocation' => [
            'class' => TsMews\Hook\AllocationCheckHook::class,
            'interface' => AbstractHook::BACKEND
        ],
		// Zuweisung mit Mews abgleichen
        'ts_matching_save_allocation' => [
            'class' => TsMews\Hook\AllocationSaveHook::class,
            'interface' => AbstractHook::BACKEND
        ],
        // Confirm
        'ts_accommodation_confirm_provider' => [
            'class' => TsMews\Hook\AllocationConfirmHook::class,
            'interface' => AbstractHook::BACKEND
        ],
        // Check-In
        'ts_inquiry_confirm_arrival' => [
            'class' => TsMews\Hook\CheckInHook::class,
            'interface' => AbstractHook::BACKEND
        ],
        // Check-Out
        'ts_inquiry_confirm_departure' => [
            'class' => TsMews\Hook\CheckOutHook::class,
            'interface' => AbstractHook::BACKEND
        ],
        // Cronjob
        'tc_cronjobs_5minutes_execute' => [
		    'class' => TsMews\Hook\CronjobHook::class,
		    'interface' => AbstractHook::BACKEND
		],
    ],

    'commands' => [
        \TsMews\Commands\MewsSync::class,
        \TsMews\Commands\MewsSyncInquiry::class
    ]

];
