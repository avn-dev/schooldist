<?php

use Core\Service\Hook\AbstractHook;
use TsTeacherLogin\Command\Attendance;

return [

	'external_apps' => [
		TsTeacherLogin\Handler\ExternalApp::APP_NAME => [
			'class' => TsTeacherLogin\Handler\ExternalApp::class
		]
	],

	'event_manager' => [
		'listen' => [
			[\TsTeacherLogin\Events\TeacherDataUpdated::class, ['access' => 'app:'.TsTeacherLogin\Handler\ExternalApp::APP_NAME]]
		],
	],

	'commands' => [
		Attendance::class,
	]

];
