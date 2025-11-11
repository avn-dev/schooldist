<?php

return [

	'parallel_processing_mapping' => [
		'transfer' => [
			'class'  => TsHubspot\Handler\ParallelProcessing\Transfer::class
		],
	],

	'external_apps' => [
		'hubspot' => [
			'class' => TsHubspot\Handler\ExternalApp::class
		]
	],

	'hooks' => [
		'ts_agency_save' => [
			'class' => TsHubspot\Hook\Transfer::class,
			'interface' => 'backend'
		],
		'ts_agency_contact_save' => [
			'class' => TsHubspot\Hook\Transfer::class,
			'interface' => 'backend'
		],
//		'ts_enquiry_save' => [
//			'class' => TsHubspot\Hook\EnquiryTransfer::class,
//			'interface' => 'backend'
//		],
		'ts_inquiry_save' => [
			'class' => TsHubspot\Hook\Transfer::class,
			'interface' => 'backend'
		],
		'ts_inquiry_saver_prepare' => [
			'class' => TsHubspot\Hook\InquiryTransfer::class,
			'interface' => 'backend'
		],
	]

];
