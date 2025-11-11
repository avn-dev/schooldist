<?php

use Core\Service\Hook\AbstractHook;

return [

	'event_manager' => [
		'listen' => [
			[\TsAccommodationLogin\Events\AccommodationDataUpdated::class, ['access' => 'app:'.\TsAccommodationLogin\Handler\ExternalApp::APP_NAME]],
			[\TsAccommodationLogin\Events\AccommodationRequestAccepted::class, ['access' => 'app:'.\TsAccommodationLogin\Handler\ExternalApp::APP_NAME]],
			[\TsAccommodationLogin\Events\AccommodationRequestRejected::class, ['access' => 'app:'.\TsAccommodationLogin\Handler\ExternalApp::APP_NAME]]
		],
	],

    'external_apps' => [
        TsAccommodationLogin\Handler\ExternalApp::APP_NAME => [
            'class' => TsAccommodationLogin\Handler\ExternalApp::class
        ]
    ],

];
