<?php

use Core\Service\Hook\AbstractHook;

return [

	'hooks' => [
		'ts_inquiry_save' => [
			'class' => TsLearncube\Hook\InquirySaveHook::class,
			'interface' => AbstractHook::BACKEND
		],
	],

	'external_apps' => [
		TsLearncube\Handler\ExternalApp::APP_NAME => [
			'class' => TsLearncube\Handler\ExternalApp::class
		]
	],
	
	'parallel_processing_mapping' => [
		'sync-inquiry' => [
			'class' => TsLearncube\Handler\ParallelProccessing\SyncInquiry::class
		],
	]

];
