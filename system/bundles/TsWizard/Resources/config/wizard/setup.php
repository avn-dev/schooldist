<?php

use TsWizard\Handler\Setup\Steps;

return [

	'start' => [
		'welcome' => [ // 'start' ist nicht möglich
			'type' => 'step',
			'title' => 'Willkommen',
			'icon' => 'fas fa-handshake',
			'class' => Steps\StepStart::class
		],
	],

	'school_block' => [
		'start' => [
			'type' => 'block',
			'elements' => [
				'welcome' => ['type' => 'step', 'title' => 'Start', 'class' => Steps\StepSchoolStart::class],
			]
		],
		'seasons' => require 'setup/school/seasons.php',
		'payment_methods' => require 'setup/school/payment_methods.php',
		'priceweeks' => require 'setup/school/priceweeks.php',
		'teachingunits' => require 'setup/school/teachingunits.php',
		'courses' => require 'setup/school/courses.php',
		'teachers' => require 'setup/school/teachers.php',
		'accommodations' => require 'setup/school/accommodations.php',
		'transfers' => require 'setup/school/transfers.php',
		'prices' => require 'setup/school/prices.php',
	],

	'main' => [
		'system' => [
			'type' => 'step',
			'title' => 'Systemeinstellungen', 'class' => Steps\SystemSettings\StepSettings::class,
			'icon' => 'fa fa-cog'
		],
		'emails' => require 'setup/emails.php',
		'schools' => require 'setup/schools.php',
		'users' => require 'setup/users.php',
	],

	'finish' => [
		'another_school' => [
			'type' => 'step',
			'title' => 'Möchten Sie eine weitere Schule hinzufügen?',
			'icon' => 'fas fa-question',
			'class' => Steps\StepAnotherSchool::class,
			'conditions' => [\TsWizard\Handler\Setup\Conditions\InstallationHasSchools::class]
		],
		'finish' => [
			'type' => 'step',
			'title' => 'Wizard abschließen',
			'icon' => 'fas fa-flag-checkered',
			'class' => Steps\StepFinish::class,
			'conditions' => [\TsWizard\Handler\Setup\Conditions\InstallationHasSchools::class]
		],
	],

];