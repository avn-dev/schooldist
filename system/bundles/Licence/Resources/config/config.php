<?php

use Core\Service\Hook\AbstractHook;

return [
	'hooks' => [
        'navigation_left' => [
            'class' => \Licence\Hook\NavigationHook::class,
            'interface' => AbstractHook::BACKEND
        ]
    ]
];