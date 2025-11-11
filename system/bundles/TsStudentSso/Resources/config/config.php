<?php

$return = [
    'external_apps' => [
        TsStudentSso\Handler\ExternalApp::APP_NAME => [
            'class' => TsStudentSso\Handler\ExternalApp::class
        ]
    ],
];

// der Provider wird automatisch mit der composer.json des Packages eingebunden
/*if(class_exists('\CodeGreenCreative\SamlIdp\LaravelSamlIdpServiceProvider')) {
	$return['providers'] = [
		\CodeGreenCreative\SamlIdp\LaravelSamlIdpServiceProvider::class
	];
}*/

return $return;
