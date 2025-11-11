<?php

namespace TsFrontend\Gui2;

class SuperordinateCourseData extends \Ext_Thebing_Gui2_Data {

	use \Tc\Traits\Gui2\Import;
	
	public static function createDialog(\Ext_Gui2 $gui) {

		$interfaceLanguage = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool()->getInterfaceLanguage();
		$translationLanguages = \Ext_Thebing_Util::getTranslationLanguages();
		
		$dialog = $gui->createDialog($gui->t('Übergeordneten Kurs bearbeiten'), $gui->t('Übergeordneten Kurs anlegen'));

		$dialog->aOptions['section'] = 'tuition_courses_superordinate';
		
		$dialog->setElement(
			$dialog->createI18NRow($gui->t('Name'), 
				[
					'db_alias' => 'ts_sc_i18n', 
					'db_column' => 'name',
					'i18n_parent_column' => 'superordinate_course_id',
					'required'	=> true
				], 
				$translationLanguages
			)
		);

		$dummyCategory = \Ext_Thebing_Tuition_Course_Category::getInstance();
		
		$dialog->setElement(
			$dialog->createRow(
				$gui->t('Kategorie'), 
				'select',
				[
					'db_alias' => 'ts_sc', 
					'db_column' => 'coursecategory_id',
					'select_options' => \Ext_Thebing_Util::addEmptyItem($dummyCategory->getArrayList(true, 'name_'.$interfaceLanguage))
				]
			)
		);

		return $dialog;
	}

	protected function getImportService(): \Ts\Service\Import\AbstractImport {
		
		$importService = new \Ts\Service\Import\SuperordinateCourse();
		$importService->setGui2Data($this);

		return $importService;
	}
 
	protected function getImportDialogId() {
		return 'SUPERORDINATE_COURSE_IMPORT_';
	}

	protected function addSettingFields(\Ext_Gui2_Dialog $oDialog) {

		$oRow = $oDialog->createRow($this->t('Vorhandene Einträge aktualisieren'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'update_existing']);
		$oDialog->setElement($oRow);

	}

}