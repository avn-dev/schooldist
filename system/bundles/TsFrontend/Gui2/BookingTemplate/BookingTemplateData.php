<?php

namespace TsFrontend\Gui2\BookingTemplate;

class BookingTemplateData extends \Ext_Gui2_Data {

	public static function createDialog(\Ext_Gui2 $gui) {

		/** @var \Ext_Thebing_Form $form */
		$form = $gui->getParent()->getDataObject()->oWDBasic;
		$schools = array_column($form->getSelectedSchools(), 'ext_1', 'id');

		$dynamicOptions = [
			'empty' => $gui->t('leer'),
			'next' => $gui->t('nächstmöglicher Eintrag')
		];

		$dialog = $gui->createDialog($gui->t('Buchungsvorlage bearbeiten'), $gui->t('Buchungsvorlage anlegen'));

		$dialog->setElement($dialog->createRow($gui->t('Beschreibung'), 'input', [
			'db_column' => 'description'
		]));

		$dialog->setElement($dialog->createRow($gui->t('Schule'), 'select', [
			'db_column' => 'school_id',
			'select_options' => \Util::addEmptyItem($schools), // Wird benötigt für dependency
			'required' => true
		]));

		$dialog->setElement($dialog->create('h4')->setElement($gui->t('Erster Kurs')));

		$dialog->setElement($dialog->createRow($gui->t('Kurs als Filter anwenden (nur Klassen)'), 'checkbox', [
			'db_column' => 'course_as_filter',
			'child_visibility' => [
				[
					'db_column' => 'course_id_locked',
					'on_values' => [0]
				],
				[
					'db_column' => 'course_from',
					'on_values' => [0]
				],
				[
					'db_column' => 'course_duration',
					'on_values' => [0]
				],
				[
					'db_column' => 'course_duration_locked',
					'on_values' => [0]
				],
//				[
//					'db_column' => 'courselanguage_id_locked',
//					'on_values' => [0]
//				]
			]
		]));

		$dialog->setElement($dialog->createRow($gui->t('Kurs'), 'select', [
			'db_column' => 'course_id',
			'selection' => new CourseSelection(),
			'dependency' => [['db_column' => 'school_id']]
		]));

		$dialog->setElement($dialog->createRow($gui->t('Kurs sperren oder ausblenden'), 'select', [
			'db_column' => 'course_id_locked',
			'select_options' => [
				'no' => $gui->t('nein'),
				'disabled' => $gui->t('gesperrt'),
				'hidden' => $gui->t('ausgeblendet'),
			],
			'child_visibility' => [
				[
					'db_column' => 'courselanguage_id_locked',
					'on_values' => ['disabled', 'hidden']
				]
			]
		]));

		$dialog->setElement($dialog->createRow($gui->t('Kurssprache'), 'select', [
			'db_column' => 'courselanguage_id',
			'selection' => new CourseLanguageSelection(),
			'format' => new \Ext_Gui2_View_Format_Null(),
			'dependency' => [['db_column' => 'course_id']]
		]));

		$dialog->setElement($dialog->createRow($gui->t('Kurssprache sperren oder ausblenden'), 'select', [
			'db_column' => 'courselanguage_id_locked',
			'select_options' => [
				'no' => $gui->t('nein'),
				'disabled' => $gui->t('gesperrt'),
				'hidden' => $gui->t('ausgeblendet'),
			]
		]));

		$dialog->setElement($dialog->createRow($gui->t('Kursstart'), 'select', [
			'db_column' => 'course_from',
			'select_options' => $dynamicOptions
		]));

		$dialog->setElement($dialog->createRow($gui->t('Kursdauer'), 'select', [
			'db_column' => 'course_duration',
			'select_options' => $dynamicOptions,
			'dependency_visibility' => [
				'db_column' => 'course_from',
				'on_values' => ['next']
			]
		]));

		$dialog->setElement($dialog->createRow($gui->t('Kursstart/Kursdauer sperren'), 'checkbox', [
			'db_column' => 'course_duration_locked',
			'dependency_visibility' => [
				'db_column' => 'course_duration',
				'on_values' => ['next']
			]
		]));

		return $dialog;

	}

}