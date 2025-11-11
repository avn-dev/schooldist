<?php

return [
	'commands' => [
		\TsEdvisor\Commands\SyncEnrollment::class
	],
	'providers' => [
		\TsEdvisor\Providers\EventServiceProvider::class
	],
	'external_apps' => [
		TsEdvisor\Handler\ExternalApp::APP_NAME => [
			'class' => TsEdvisor\Handler\ExternalApp::class
		]
	],
	'hooks' => [
		'ts_inquiry_dialog_data' => [
			'class' => \TsEdvisor\Hooks\InquiryDialogDataHook::class
		],
		'ts_inquiry_get_creator' => [
			'class' => \TsEdvisor\Hooks\InquiryGetCreatorHook::class
		],
		'ts_inquiry_get_creator_options' => [
			'class' => \TsEdvisor\Hooks\InquiryGetCreatorOptionsHook::class
		]
	]
];
