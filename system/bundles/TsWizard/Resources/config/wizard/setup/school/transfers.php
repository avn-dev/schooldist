<?php

use TsWizard\Handler\Setup\Steps\Transfer\StepSkip;
use TsWizard\Handler\Setup\Steps\TransferLocation;
use TsWizard\Handler\Setup\Conditions\School;

return [
	'type' => 'block',
	'title' => 'Transfer',
	'icon' => 'fa fa-car',
	'right' => ['thebing_pickup_icon', ''],
	'elements' => [
		'skip' => [
			'type' => 'step',
			'title' => 'Möchten Sie die Transfere überspringen?',
			'icon' => 'fas fa-question',
			'class' => StepSkip::class,
		],
		'resources' => [
			'type' => 'block',
			'elements' => [
				'locations' => [
					'type' => 'block',
					'class' => TransferLocation\BlockTransferLocations::class,
					'elements' => [
						'apply' => ['type' => 'step', 'title' => 'Von anderen Schulen übernehmen', 'class' => TransferLocation\StepApply::class],
						'list' => ['type' => 'step', 'title' => 'Reiseziele', 'icon' => 'fas fa-th-list', 'class' => TransferLocation\StepList::class],
						'form' => [
							'type' => 'block',
							'class' => TransferLocation\BlockTransferLocationEntity::class,
							'elements' => [
								'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => TransferLocation\StepSettings::class]
							]
						]
					],
				],
				'provider' => [
					'type' => 'step',
					'title' => 'Anbieter',
					'icon' => 'fas fa-th-list',
					'class' => \Tc\Service\Wizard\StepRedirect::class,
					'redirect' => 'navigation:ts.transfer.resources.companies',
					'conditions' => [
						School\HasTransferLocations::class
					]
				],
				'packages' => [
					'type' => 'step',
					'title' => 'Transferpakete',
					'icon' => 'fas fa-th-list',
					'class' => \Tc\Service\Wizard\StepRedirect::class,
					'redirect' => 'navigation:ts.transfer.resources.packages',
					'conditions' => [
						School\HasTransferLocations::class
					]
				]
			]
		],
	]
];