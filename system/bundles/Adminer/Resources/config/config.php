<?php

return [
	'hooks' => [
		'navigation_top' => [
			'class' => Adminer\Hook\NavigationHook::class,
			'interface' => Core\Service\Hook\AbstractHook::BACKEND
		]
	]
];
