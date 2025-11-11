<?php

use TsWizard\Handler\Setup\Steps\Accommodation;
use TsWizard\Handler\Setup\Steps\AccommodationCategory;
use TsWizard\Handler\Setup\Steps\AccommodationRoomType;
use TsWizard\Handler\Setup\Steps\AccommodationMeal;
use TsWizard\Handler\Setup\Conditions;

return [
	'type' => 'block',
	'title' => 'Unterkünfte',
	'icon' => 'fas fa-home',
	'right' => ['thebing_accommodation_icon', ''],
	'elements' => [
		'skip' => [
			'type' => 'step',
			'title' => 'Möchten Sie die Unterkünfte überspringen?',
			'icon' => 'fas fa-question',
			'class' => Accommodation\StepSkip::class,
		],
		'resources' => [
			'type' => 'block',
			'elements' => [
				'categories' => [
					'type' => 'block',
					'title' => 'Kategorien',
					'class' => AccommodationCategory\BlockAccommodationCategories::class,
					'elements' => [
						'apply' => ['type' => 'step', 'title' => 'Von anderen Schulen übernehmen', 'class' => AccommodationCategory\StepApply::class],
						'list' => ['type' => 'step', 'title' => 'Übersicht', 'icon' => 'fas fa-th-list', 'class' => AccommodationCategory\StepList::class],
						'form' => [
							'type' => 'block',
							'class' => AccommodationCategory\BlockAccommodationCategoryEntity::class,
							'elements' => [
								'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => AccommodationCategory\StepSettings::class]
							]
						]
					],
				],
				'room_types' => [
					'type' => 'block',
					'title' => 'Räume',
					'class' => AccommodationRoomType\BlockAccommodationRoomTypes::class,
					'elements' => [
						'apply' => ['type' => 'step', 'title' => 'Von anderen Schulen übernehmen', 'class' => AccommodationRoomType\StepApply::class],
						'list' => ['type' => 'step', 'title' => 'Übersicht', 'icon' => 'fas fa-th-list', 'class' => AccommodationRoomType\StepList::class],
						'form' => [
							'type' => 'block',
							'class' => AccommodationRoomType\BlockAccommodationRoomTypeEntity::class,
							'elements' => [
								'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => AccommodationRoomType\StepSettings::class]
							]
						]
					],
				],
				'meals' => [
					'type' => 'block',
					'title' => 'Verpflegung',
					'class' => AccommodationMeal\BlockAccommodationMeals::class,
					'elements' => [
						'apply' => ['type' => 'step', 'title' => 'Von anderen Schulen übernehmen', 'class' => AccommodationMeal\StepApply::class],
						'list' => ['type' => 'step', 'title' => 'Übersicht', 'icon' => 'fas fa-th-list', 'class' => AccommodationMeal\StepList::class],
						'form' => [
							'type' => 'block',
							'class' => AccommodationMeal\BlockAccommodationMealEntity::class,
							'elements' => [
								'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => AccommodationMeal\StepSettings::class]
							]
						]
					],
				],
				'providers' => [
					'type' => 'step',
					'title' => 'Unterkunftsanbieter',
					'class' => \Tc\Service\Wizard\StepRedirect::class,
					'redirect' => 'navigation:ts.accommodation.resources.providers',
					'conditions' => [
						Conditions\School\HasAccommodationCategories::class,
						Conditions\School\HasAccommodationMeals::class,
						Conditions\School\HasAccommodationRoomTypes::class,
						Conditions\School\HasPriceweeks::class,
					]
				]
			],
		],
	]
];