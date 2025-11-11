<?php


return [

	/*
	|--------------------------------------------------------------------------
	| Default Mailer
	|--------------------------------------------------------------------------
	|
	| This option controls the default mailer that is used to send any email
	| messages sent by your application. Alternative mailers may be setup
	| and used as needed; however, this mailer will be used by default.
	|
	*/

	'default' => \System::d('mailer_default', 'smtp'),

	/*
	|--------------------------------------------------------------------------
	| Mailer Configurations
	|--------------------------------------------------------------------------
	|
	| Here you may configure all of the mailers used by your application plus
	| their respective settings. Several examples have been configured for
	| you and you are free to add your own as your application requires.
	|
	| Laravel supports a variety of mail "transport" drivers to be used while
	| sending an e-mail. You will specify which one you are using for your
	| mailers below. You are free to add additional mailers as required.
	|
	| Supported: "smtp", "sendmail", "mailgun", "ses",
	|            "postmark", "log", "array", "failover"
	|
	*/

	'mailers' => [
		'smtp' => [
			'transport' => 'smtp',
			'host' => \System::d('smtp_host'),
			'port' => \System::d('smtp_port', 25),
			'encryption' => strtolower(\System::d('smtp_encryption', 'tls')),
			'username' => \System::d('smtp_user'),
			'password' => \System::d('smtp_password'),
			'timeout' => null,
			'auth_mode' => \System::d('smtp_auth_mode', 'password'),
			'oauth2_provider' => \System::d('smtp_oauth2_provider', ''),
			'oauth2_token_data' => json_decode(\System::d('smtp_oauth2_data', ''), true)
		],

		'sendmail' => [
			'transport' => 'sendmail',
			'path' => \System::d('mailer_default', ini_get('sendmail_path')),
		],

	],

	/*
	|--------------------------------------------------------------------------
	| Global "From" Address
	|--------------------------------------------------------------------------
	|
	| You may wish for all e-mails sent by your application to be sent from
	| the same address. Here, you may specify a name and address that is
	| used globally for all e-mails that are sent by your application.
	|
	*/

	'from' => [
		'address' => System::d('admin_email'),
		'name' => System::d('project_name'),
	],

	/*
	|--------------------------------------------------------------------------
	| Markdown Mail Settings
	|--------------------------------------------------------------------------
	|
	| If you are using Markdown based email rendering, you may configure your
	| theme and component paths here, allowing you to customize the design
	| of the emails. Or, you may simply stick with the Laravel defaults!
	|
	*/

	/*'markdown' => [
		'theme' => 'default',

		'paths' => [
			resource_path('views/vendor/mail'),
		],
	],*/

];