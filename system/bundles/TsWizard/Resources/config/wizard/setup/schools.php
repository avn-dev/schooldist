<?php

use TsWizard\Handler\Setup\Steps\School;
use TsWizard\Handler\Setup\Conditions;

return [
	'type' => 'block',
	'conditions' => [Conditions\InstallationHasEmailAccounts::class],
	'elements' => [
		'list' => [
			'type' => 'step', 'title' => 'Schulübersicht', 'icon' => 'fas fa-school', 'class' => School\StepList::class],
		'form' => [
			'type' => 'block',
			'class' => School\BlockSchool::class,
			'elements' => [
				'settings' => ['type' => 'step', 'title' => 'Schule', 'class' => School\StepSettings::class],
				'settings2' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => School\StepSettings2::class, 'conditions' => [Conditions\ExistingSchool::class]],
				'settings3' => ['type' => 'step', 'title' => 'Buchhaltung', 'class' => School\StepSettings3::class, 'conditions' => [Conditions\ExistingSchool::class]],
				'letterhead' => ['type' => 'step', 'title' => 'Briefkopf', 'class' => School\StepLetterhead::class, 'conditions' => [Conditions\ExistingSchool::class]]
			]
		]
	]
];