<?php

use TsWizard\Handler\Setup\Steps\PriceWeek;

return [
	'type' => 'block',
	'title' => 'Preiswochen',
	'icon' => 'fa fa-rocket',
	'class' => PriceWeek\BlockPriceWeeks::class,
	'elements' => [
		'apply' => ['type' => 'step', 'title' => 'Von anderen Schulen übernehmen', 'class' => PriceWeek\StepApply::class],
		'list' => ['type' => 'step', 'title' => 'Übersicht aller Preiswochen', 'icon' => 'fas fa-th-list', 'class' => PriceWeek\StepList::class],
		'form' => [
			'type' => 'block',
			'class' => PriceWeek\BlockPriceWeekEntity::class,
			'elements' => [
				'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => PriceWeek\StepSettings::class]
			]
		]
	]
];