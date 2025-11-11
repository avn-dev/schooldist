<?php

return [

	'mail_oauth2' => [
		'google' => [
			/*
			 * https://developers.google.com/gmail/api/auth/scopes
			 */
			'match' => '(.*).gmail.com',
			'scopes' => 'https://mail.google.com/'
		],
		'microsoft' => [
			'match' => ['(.*).office365.com', '(.*).outlook.com'],
			//'scopes' => ['wl.offline_access', 'wl.signin', 'wl.emails', 'wl.imap'],
			'scopes' => ['https://outlook.office365.com/IMAP.AccessAsUser.All', 'https://outlook.office365.com/SMTP.Send', 'offline_access']
		]
	],

	'parallel_processing_mapping' => [
		'webhook' => [
			'class' => \Api\Handler\ParallelProcessing\Webhook::class
		],
	]

];