<?php

use TsWizard\Handler\Setup\Steps;

return [
	'type' => 'block',
	'title' => 'Kurse',
	'icon' => 'fas fa-ruler-combined',
	'elements' => [
		'course_categories' => [
			'type' => 'block',
			'title' => 'Kurskategorien',
			'icon' => 'fas fa-th-list',
			'class' => Steps\CourseCategory\BlockCategories::class,
			'elements' => [
				'apply' => ['type' => 'step', 'title' => 'Von anderen Schulen übernehmen', 'class' => Steps\CourseCategory\StepApply::class],
				'list' => ['type' => 'step', 'title' => 'Übersicht aller Kurskategorien', 'icon' => 'fas fa-th-list', 'class' => Steps\CourseCategory\StepList::class],
				'form' => [
					'type' => 'block',
					'class' => Steps\CourseCategory\BlockCategoryEntity::class,
					'elements' => [
						'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => Steps\CourseCategory\StepSettings::class]
					]
				]
			],
		],
		'course_languages' => [
			'type' => 'block',
			'class' => Steps\CourseLanguage\BlockLanguages::class,
			'elements' => [
				'list' => ['type' => 'step', 'title' => 'Kurssprachen', 'icon' => 'fas fa-th-list', 'class' => Steps\CourseLanguage\StepList::class],
				'form' => [
					'type' => 'block',
					'class' => Steps\CourseLanguage\BlockLanguageEntity::class,
					'elements' => [
						'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => Steps\CourseLanguage\StepSettings::class]
					]
				]
			],
		],
		'courses' => [
			'type' => 'step',
			'title' => 'Kurse',
			'icon' => 'fas fa-th-list',
			'class' => \Tc\Service\Wizard\StepRedirect::class,
			'redirect' => 'navigation:ts.tuition.resources.courses',
			'conditions' => [
				\TsWizard\Handler\Setup\Conditions\School\HasPriceweeks::class,
				\TsWizard\Handler\Setup\Conditions\School\HasTeachingUnits::class,
			]
		],
		'buildings' => [
			'type' => 'block',
			'class' => Steps\Building\BlockBuildings::class,
			'elements' => [
				'list' => ['type' => 'step', 'title' => 'Gebäude', 'icon' => 'fas fa-th-list', 'class' => Steps\Building\StepList::class],
				'form' => [
					'type' => 'block',
					'class' => Steps\Building\BlockBuildingEntity::class,
					'elements' => [
						'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => Steps\Building\StepSettings::class],
						'floors' => [
							'type' => 'block',
							'title' => 'Etagen',
							'class' => Steps\Building\Floor\BlockFloors::class,
							'elements' => [
								'list' => ['type' => 'step', 'title' => 'Übersicht aller Etagen', 'icon' => 'fas fa-th-list', 'class' => Steps\Building\Floor\StepList::class],
								'form' => [
									'type' => 'block',
									'class' => Steps\Building\Floor\BlockFloorEntity::class,
									'elements' => [
										'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => Steps\Building\Floor\StepSettings::class],
									]
								]
							],
						]
					]
				]
			],
		],
		'classrooms' => [
			'type' => 'block',
			'class' => Steps\Classroom\BlockClassrooms::class,
			'conditions' => [
				\TsWizard\Handler\Setup\Conditions\School\HasBuildings::class,
			],
			'elements' => [
				'list' => ['type' => 'step', 'title' => 'Klassenzimer', 'icon' => 'fas fa-th-list', 'class' => Steps\Classroom\StepList::class],
				'form' => [
					'type' => 'block',
					'class' => Steps\Classroom\BlockClassroomEntity::class,
					'elements' => [
						'settings' => ['type' => 'step', 'title' => 'Einstellungen', 'class' => Steps\Classroom\StepSettings::class]
					]
				]
			],
		]
	]
];