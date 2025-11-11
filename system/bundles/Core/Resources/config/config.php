<?php

// Achtung! Serviceproviders in config/app.php hinterlegen, da diese als erstes registriert werden sollten

return [
	'parallel_processing_mapping' => [
		'check-handler' => [
			'class' => Core\Handler\ParallelProcessing\CheckHandler::class
		],
		'logging-handler' => [
			'class' => Core\Handler\ParallelProcessing\LoggingHandler::class
		],
		'notification-send' => [
			'class' => Core\Handler\ParallelProcessing\SendNotification::class
		]
	],
	'commands' => [
		\Illuminate\Foundation\Console\PackageDiscoverCommand::class,
		Core\Command\KeyGenerate::class,
		Core\Command\ParallelProcessing\Execute::class,
		Core\Command\ParallelProcessing\Stack::class,
		Core\Command\Composer::class,
		Core\Command\Composer\Build::class,
		Core\Command\Scheduler::class,
		Core\Command\Routing\Update::class,
		Core\Command\Routing\Info::class,
		Core\Command\Cache\Clear::class,
		Core\Command\Cache\Forget::class,
		Core\Command\Globalchecks\Execute::class,
		Core\Command\L10N\BackendDepuration::class,
		Core\Command\Bundles\Config::class,
		Core\Command\Npm\Build::class,
		Core\Command\Database\SqlFileMigration::class,
	],
	'webpack' => [
		['entry' => '../../../../node_modules/jquery/dist/jquery.min.{js,map}', 'output' => 'jquery', 'config' => 'backend', 'rule' => 'copy'],
		['entry' => '../../../../node_modules/jquery-ui-dist/*.min.{js,css}', 'output' => 'jquery', 'config' => 'backend', 'rule' => 'copy'],
		['entry' => '../../../../node_modules/jquery-ui-dist/images', 'output' => 'jquery/images', 'config' => 'backend', 'rule' => 'copy'],
		['entry' => '../../../../node_modules/v-calendar/dist/style.css', 'output' => 'v-calendar', 'config' => 'backend', 'rule' => 'copy'],
	]
];
