<?php

use Core\Service\Hook\AbstractHook;

return [

	'hooks' => [
		'ts_inquiry_create' => [
			'class' => TsRdstation\Hook\InquiryCreateHook::class
		],
		'ts_inquiry_update' => [
			'class' => TsRdstation\Hook\InquiryUpdateHook::class
		],
		'ts_enquiry_create' => [
			'class' => TsRdstation\Hook\EnquiryCreateHook::class
		],
		'ts_enquiry_update' => [
			'class' => TsRdstation\Hook\EnquiryUpdateHook::class
		],
		'ts_inquiry_payment_create' => [
			'class' => TsRdstation\Hook\PaymentCreateHook::class
		],
		'ts_inquiry_cancel' => [
			'class' => TsRdstation\Hook\InquiryCancelHook::class
		],
	],

	'external_apps' => [
		TsRdstation\Handler\ExternalApp::APP_NAME => [
			'class' => TsRdstation\Handler\ExternalApp::class
		]
	],
	
	'parallel_processing_mapping' => [
		'sync-inquiry' => [
			'class' => TsRdstation\Handler\ParallelProcessing\SyncInquiry::class
		],
		'sync-enquiry' => [
			'class' => TsRdstation\Handler\ParallelProcessing\SyncEnquiry::class
		],
		'sync-payment' => [
			'class' => TsRdstation\Handler\ParallelProcessing\SyncPayment::class
		]		
	],

	'rdstation' => [
		'client_id' => '1f6d7af1-566e-4dab-83f5-1624f081880b',
		'client_secret' => 'b1f549ccb62b49908c29b5ffc03ff05d'
	]
	
];
