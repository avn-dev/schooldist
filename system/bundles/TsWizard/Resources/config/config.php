<?php

return [

	'hooks' => [
		'admin_tabs' => [
			'class' => \TsWizard\Hook\AdminTabs::class,
			'interface' => Core\Service\Hook\AbstractHook::BACKEND
		],
		'navigation_left' => [
			'class' => \TsWizard\Hook\NavigationHook::class,
			'interface' => Core\Service\Hook\AbstractHook::BACKEND
		]
	],

];