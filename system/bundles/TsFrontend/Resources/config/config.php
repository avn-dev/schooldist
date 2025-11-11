<?php

return [
	'parallel_processing_mapping' => [
		'form-payment' => [
			'class' => TsFrontend\Handler\ParallelProcessing\FormPayment::class
		]
	],
	'external_apps' => [
		'paypal' => [
			'class' => 'TsFrontend\ExternalApps\PayPal'
		],
		'redsys' => [
			'class' => 'TsFrontend\ExternalApps\Redsys'
		],
		'stripe' => [
			'class' => 'TsFrontend\ExternalApps\Stripe'
		],
		'klarna' => [
			'class' => TsFrontend\ExternalApps\Klarna::class
		],
		\TsFrontend\ExternalApps\TransferMate::KEY => [
			'class' => TsFrontend\ExternalApps\TransferMate::class
		],
		'moneris' => [
			'class' => TsFrontend\ExternalApps\Moneris::class
		],
	],
	'hooks' => [
		'page_data' => [
			'class' => TsFrontend\Hook\TrackingCookieHook::class,
			'interface' => \Core\Service\Hook\AbstractHook::FRONTEND
		]
	],
	'factory_allocations' => [
		\TcFrontend\Events\FeedbackFormSaved::class => \TsFrontend\Events\FeedbackFormSaved::class
	],
	'event_manager' => [
		'listen' => [
			[\TsFrontend\Events\PlacementtestResult::class, ['access' => ['ts_event_manager_frontend', 'placementtest']]],
			[\TsFrontend\Events\FeedbackFormSaved::class, ['access' => ['ts_event_manager_frontend', 'feedback_form']]]
		],
	],
	'webpack' => [
		// TsFrontend als Alias bereitstellem
		['entry' => null, 'output' => null, 'config' => 'frontend'],
	]
];
