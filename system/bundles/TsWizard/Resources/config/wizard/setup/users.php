<?php

use TsWizard\Handler\Setup\Steps\User;
use TsWizard\Handler\Setup\Conditions;

return [
	'type' => 'block',
	'conditions' => [
		Conditions\InstallationHasSchools::class
	],
	'elements' => [
		'list' => ['type' => 'step', 'title' => 'Benutzer', 'icon' => 'fa fa-users', 'class' => User\StepList::class],
		'form' => [
			'type' => 'block',
			'class' => User\BlockUser::class,
			'elements' => [
				'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'icon' => 'fa fa-user', 'class' => User\StepSettings::class]
			]
		],
		'import' => [
			'type' => 'block',
			'class' => User\BlockUserImport::class,
			'elements' => [
				'settings' => ['type' => 'step', 'title' => 'Importieren', 'icon' => 'fa fa-upload', 'class' => User\StepImport::class]
			]
		]
	]
];