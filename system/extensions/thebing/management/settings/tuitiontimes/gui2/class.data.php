<?php

class Ext_Thebing_Management_Settings_TuitionTimes_Gui2_Data extends Ext_TC_Config_Child_Gui2_Data {

	public static function addSettings(Ext_Gui2 $gui, array &$config) {

		$schools = \Ext_Thebing_Client::getSchoolList(false, 0, true);

//		$courseCategories = collect(Ext_Thebing_Tuition_Course_Category::getRepository()
//			->findAll())
//			->mapWithKeys(function (Ext_Thebing_Tuition_Course_Category $category) {
//				return [$category->id => $category->getName()];
//			});
//
//		$config['ts_statistic_tuition_teacherforecast_coursecategories'] = [
//			'description' => sprintf('%s: %s', $gui->t('Lehrerbedarf'), $gui->t('Kurskategorien')),
//			'type' => 'select',
//			'select_options' => $courseCategories,
//			'multiple' => 5,
//			'jquery_multiple' => 1
//		];

		$gui2 = (new \Ext_Gui2_Factory('tsStatistic_settings_tuition_times'))->createGui();

		$config['ts_statistic_tuition_times'] = [
			'description' => sprintf('%s: %s', $gui->t('Klassenplanung'), $gui->t('Standardzeiten / Tage / Kurse')),
			'type' => 'gui',
			'gui' => $gui2
		];

		$classRooms = [];
		foreach ($schools as $school) {
			$classRooms += array_map(function ($sLabel) use ($school) {
				return sprintf('%s: %s', $school->short, $sLabel);
			}, $school->getClassRooms(true, null, false));
		}

		$config['ts_statistic_tuition_rooms'] = [
			'description' => sprintf('%s: %s', $gui->t('Raumbedarf'), $gui->t('Räume')),
			'type' => 'select',
			'select_options' => $classRooms,
			'multiple' => 5,
			'jquery_multiple' => 1
		];

	}

	public static function getDialog(Ext_Gui2 $gui): Ext_Gui2_Dialog {

		$title = sprintf('%s: %s', $gui->t('Klassenplanung'), $gui->t('Standardzeiten / Tage / Kurse'));
		$dialog = $gui->createDialog($title, $title);

		$dialog->setElement($dialog->createRow($gui->t('Zeit'), 'select', [
			'db_column' => 'tuition_time_id',
			'selection' => new Ext_Thebing_Management_Settings_TuitionTimes_Gui2_Selection_TuitionTime(),
			'required' => true
		]));

		$dialog->setElement($dialog->createRow($gui->t('Tage'), 'select', [
			'db_column' => 'days',
			'select_options' => self::getDayOptions(),
			'multiple' => 5,
			'jquery_multiple' => true,
			'required' => true
		]));

		$dialog->setElement($dialog->createRow($gui->t('Kurse'), 'select', [
			'db_column' => 'courses',
			'selection' => new Ext_Thebing_Management_Settings_TuitionTimes_Gui2_Selection_Course(),
			'multiple' => 5,
			'jquery_multiple' => true,
			'searchable' => true,
			'required' => true,
			'dependency' => [
				['db_column' => 'tuition_time_id'],
				//['db_column' => 'days']
			],
		]));

		return $dialog;

	}

	public static function getCourseOptions() {

		$schools = \Ext_Thebing_Client::getSchoolList(false, 0, true);

		$courses = [];
		foreach ($schools as $school) {
			$courses += array_map(function ($sLabel) use ($school) {
				return sprintf('%s: %s', $school->short, $sLabel);
			}, $school->getCourseList());
		}

		return $courses;

	}

	public static function getDayOptions() {
		return \Ext_TC_Util::getLocaleDays(System::getInterfaceLanguage(), 'wide');
	}

	public static function getTuitionTemplateOptions() {

		$schools = \Ext_Thebing_Client::getSchoolList(false, 0, true);

		$tuitionTemplates = [];
		foreach ($schools as $school) {
			$tuitionTemplates += array_map(function ($sLabel) use ($school) {
				return sprintf('%s: %s', $school->short, $sLabel);
			}, $school->getTuitionTemplates(true));
		}

		return $tuitionTemplates;

	}

}