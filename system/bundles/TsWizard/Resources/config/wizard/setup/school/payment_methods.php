<?php

use TsWizard\Handler\Setup\Steps\PaymentMethod;

return [
	'type' => 'block',
	'title' => 'Zahlmethoden',
	'icon' => 'fas fa-th-list',
	'class' => PaymentMethod\BlockPaymentMethods::class,
	'elements' => [
		'apply' => ['type' => 'step', 'title' => 'Von anderen Schulen übernehmen', 'class' => PaymentMethod\StepApply::class],
		'list' => ['type' => 'step', 'title' => 'Übersicht aller Zahlmethoden', 'icon' => 'fas fa-th-list', 'class' => PaymentMethod\StepList::class],
		'form' => [
			'type' => 'block',
			'class' => PaymentMethod\BlockPaymentMethodEntity::class,
			'elements' => [
				'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => PaymentMethod\StepSettings::class]
			]
		]
	],
];