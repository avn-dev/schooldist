<?php

use TsWizard\Handler\Setup\Steps\AdditionalCost;

return [
	'type' => 'block',
	'title' => 'Preise',
	'icon' => 'fa fa-euro-sign',
	'elements' => [
		'additionalcosts' => [
			'type' => 'block',
			'elements' => [
				'list' => [
					'type' => 'step',
					'title' => 'Zusatzgebüren',
					'icon' => 'fas fa-plus-circle',
					'class' => \Tc\Service\Wizard\StepRedirect::class,
					'redirect' => 'navigation:ts.marketing.resources.additionalcosts'
				]
				/*'list' => ['type' => 'step', 'title' => 'Zusatzgebüren', 'icon' => 'fas fa-plus-circle', 'class' => AdditionalCost\StepList::class],
				'form' => [
					'type' => 'block',
					'class' => AdditionalCost\BlockAdditionalCostEntity::class,
					'elements' => [
						'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => AdditionalCost\StepSettings::class]
					]
				]*/
			],
		],
		'pricelist' => [
			'type' => 'step',
			'title' => 'Preise - Allgemein',
			'icon' => 'fas fa-tags',
			'class' => \Tc\Service\Wizard\StepRedirect::class,
			'redirect' => 'navigation:ts.marketing.prices.main'
		]
	]
];