<?php

use Core\Service\Hook\AbstractHook;

return [

	'hooks' => [
		'ts_navigation_left' => [
			'class' => TsScreen\Hook\NavigationLeftHook::class,
			'interface' => AbstractHook::BACKEND
		]
	],
	
	'external_apps' => [
		\TsScreen\Service\ScreenApp::APP_NAME => [
			'class' => \TsScreen\Service\ScreenApp::class
		]
	]
	
];

