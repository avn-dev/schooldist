<?php

return [
	'parallel_processing_mapping' => [
		'inquiry-task' => [
			'class' => TsRegistrationForm\Handler\ParallelProcessing\InquiryTask::class
		],
		//'mail-task' => [
		//	'class' => TsRegistrationForm\Handler\ParallelProcessing\MailTask::class
		//]
	],
	'event_manager' => [
		'listen' => [
			[\TsRegistrationForm\Events\FormSaved::class, ['access' => ['ts_event_manager_frontend', 'form_saved']]],
			[\TsRegistrationForm\Events\PdfCreationFailed::class, ['access' => ['ts_event_manager_frontend', 'pdf_failed']]]
		],
	],
	'webpack' => [
		['entry' => 'js/registration.js', 'output' => '&', 'config' => 'frontend', 'js_npm_include' => 'vue-plugin-load-script'],
		['entry' => 'scss/registration.scss', 'output' => '&', 'config' => 'frontend'],
		['entry' => 'scss/registration-bootstrap4.scss', 'output' => '&', 'config' => 'frontend']
	]
];
