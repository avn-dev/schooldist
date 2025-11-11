<?php

return [

	'authenticators' => [
		'simple' => \Admin\Service\Auth\AuthenticationAddon\Simple::class,
		'passkeys' => \Admin\Service\Auth\AuthenticationAddon\Passkey::class,
		'passkeys_extern' => \Admin\Service\Auth\AuthenticationAddon\Passkey::class,
		'googletwofactor' => \Admin\Service\Auth\AuthenticationAddon\GoogleTwoFactor::class
	],
	'parallel_processing_mapping' => [
		'dashboard' => [
			'class' => \Admin\Handler\ParallelProcessing\Dashboard::class
		],
		// @deprecated
		'admin-mail' => [
			'class' => \Admin\Handler\ParallelProcessing\AdminMail::class
		],
		'notification-send' => [
			'class' => \Core\Handler\ParallelProcessing\SendNotification::class
		],

	],

	'hooks' => [
		\Core\Command\Scheduler::HOOK_NAME => [
			'class' => \Admin\Hooks\SchedulerHook::class,
			'interface' => \Core\Service\Hook\AbstractHook::BACKEND
		]
	],

	'commands' => [
		\Admin\Commands\Build::class,
		\Admin\Commands\Notification::class,
		\Admin\Commands\Dashboard::class,
	],

	'log_messages' => [
		Access_Backend::LOG_LOGIN_FAILED => 'Einloggen fehlgeschlagen! Ihr Lizenzschlüssel ist ungültig!',
		Access_Backend::LOG_LOGIN_SUCCESSFUL => 'Benutzer eingeloggt.',
		Access_Backend::LOG_USER_LOCKED => 'Einloggen fehlgeschlagen! Benutzer "{username}" wurde vorrübergehend gesperrt.', // TODO Rechtschreibfehler
		Access_Backend::LOG_WRONG_PASSWORD => 'Einloggen fehlgeschlagen! Falsches Passwort für Benutzer "{username}".',
		Access_Backend::LOG_UNKNOWN_USER => 'Einloggen fehlgeschlagen! Unbekannter Benutzer "{username}".'
	],

	'webpack' => [
		//['entry' => 'js/app.ts', 'output' => '&', 'config' => 'backend'],
		//['entry' => 'js/frame.js', 'output' => '&', 'config' => 'backend', 'library' => ['name' => ['__FIDELO__'], 'type' => 'assign-properties']],
		['entry' => 'js/app.ts', 'output' => '&', 'config' => 'backend'],
		['entry' => 'js/auth.ts', 'output' => '&', 'config' => 'backend'],
		//['entry' => 'js/build.ts', 'output' => 'js/components.js', 'config' => 'backend'],
		['entry' => 'js/iframe.ts', 'output' => 'js/admin-iframe.js', 'config' => 'backend', 'library' => ['name' => ['__ADMIN__'], 'type' => 'assign-properties']],
		['entry' => 'scss/tailwind.scss', 'output' => '&', 'config' => 'backend'],
		['entry' => 'scss/app.scss', 'output' => '&', 'config' => 'backend'],
		['entry' => 'scss/bootstrap-wrapper.scss', 'output' => '&', 'config' => 'backend'],
		['entry' => 'scss/custom.scss', 'output' => '&', 'config' => 'backend'],
	]

];
