<?php

use TsWizard\Handler\Setup\Steps\Season;

return [
	'type' => 'block',
	'class' => Season\BlockSeasons::class,
	'elements' => [
		'list' => ['type' => 'step', 'title' => 'Saisons', 'icon' => 'fa fa-star', 'class' => Season\StepList::class],
		'form' => [
			'type' => 'block',
			'class' => Season\BlockSeasonEntity::class,
			'elements' => [
				'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => Season\StepSettings::class]
			]
		]
	]
];