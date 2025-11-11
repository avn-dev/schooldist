<?php

use Core\Service\Hook\AbstractHook;

return [

	'hooks' => [
		'ts_inquiry_gui2_hook' => [
			'class' => TsCanvas\Hook\InquiryGui2Hook::class,
			'interface' => AbstractHook::BACKEND
		]
	],

	'external_apps' => [
		TsCanvas\Handler\ExternalApp::APP_NAME => [
			'class' => TsCanvas\Handler\ExternalApp::class
		]
	]
];
