<?php

use TsWizard\Handler\Setup\Steps\TeachingUnit;

return [
	'type' => 'block',
	'title' => 'Lektionen',
	'icon' => 'fa fa-rocket',
	'class' => TeachingUnit\BlockTeachingUnits::class,
	'elements' => [
		'apply' => ['type' => 'step', 'title' => 'Von anderen Schulen übernehmen', 'class' => TeachingUnit\StepApply::class],
		'list' => ['type' => 'step', 'title' => 'Übersicht aller Lektion', 'icon' => 'fas fa-th-list', 'class' => TeachingUnit\StepList::class],
		'form' => [
			'type' => 'block',
			'class' => TeachingUnit\BlockTeachingUnitEntity::class,
			'elements' => [
				'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => TeachingUnit\StepSettings::class]
			]
		]
	]
];