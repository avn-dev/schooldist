<?php

use TsWizard\Handler\Setup\Steps\Season;

return [
	'type' => 'block',
	'title' => 'Lehrerverwaltung',
	'icon' => 'fas fa-chalkboard-teacher',
	'elements' => [
		'list' => [
			'type' => 'step',
			'title' => 'Lehrer',
			'icon' => 'fas fa-th-list',
			'class' => \Tc\Service\Wizard\StepRedirect::class,
			'redirect' => 'navigation:ts.tuition.teachers.list'
		],
		/*'costs' => [
			'type' => 'step',
			'title' => 'Kosten',
			'icon' => 'fas fa-th-list',
			'class' => \Tc\Service\Wizard\StepRedirect::class,
			'redirect' => 'navigation:marketing_prices_costs'
		]*/
	]
];