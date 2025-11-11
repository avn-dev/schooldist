<?php

return [
	'hooks' => [
        'tc_cronjobs_hourly_execute' => [
            'class' => \TsFlywire\Hook\HourlyCronjobHook::class,
        ],
		'ts_events_new_payment_sources' => [
			'class' => \TsFlywire\Hook\PaymentSourcesOptions::class,
		]
    ],

	'external_apps' => [
		TsFlywire\Handler\ExternalAppSync::APP_NAME => [
			'class' => TsFlywire\Handler\ExternalAppSync::class
		],
		\TsFlywire\Handler\ExternalAppWidget::APP_NAME => [
			'class' => TsFlywire\Handler\ExternalAppWidget::class
		]
	],

	'commands' => [
		\TsFlywire\Commands\FileSync::class
	]
	
];