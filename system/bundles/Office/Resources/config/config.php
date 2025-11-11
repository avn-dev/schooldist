<?php

return [
	'hooks' => [
		'navigation_top' => [
			'class' => \Office\Hook\NavigationTopHook::class,
			'interface' => Core\Service\Hook\AbstractHook::BACKEND
		],
        'navigation_left' => [
            'class' => \Office\Hook\NavigationLeftHook::class,
			'interface' => Core\Service\Hook\AbstractHook::BACKEND
        ]
	],
	'commands' => [
		\Office\Command\Cronjob::class
	]
];
