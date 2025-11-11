<?php

class Ext_TS_System_Checks_Tuition_CourseCategoriesAndLanguagesI18N extends GlobalChecks {

	public function getTitle() {
		return 'Migration of course categories and course languages';
	}

	public function getDescription() {
		return 'Course categories and course languages become multilingual.';
	}

	public function executeCheck() {

		$fields = DB::describeTable('kolumbus_tuition_levelgroups', true);
		if(isset($fields['title'])) {
			// Reihenfolge für updateLanguageFields
			DB::addField('kolumbus_tuition_levelgroups', 'name', 'VARCHAR(255) NOT NULL', 'title');
		}

		Ext_Thebing_Util::updateLanguageFields();

		$this->migrateCourseCategories();
		$this->migrateCourseLanguages();
		$this->setCourseCategoriesSort();

		return true;

	}

	private function migrateCourseCategories() {

		$fields = DB::describeTable('ts_tuition_coursecategories', true);
		if(!isset($fields['name'])) {
			return true;
		}

		Util::backupTable('ts_tuition_coursecategories');

		$languages = (array)Ext_TS_Config::getInstance()->frontend_languages;
		foreach ($languages as $language) {
			$field = 'name_'.$language;
			DB::executeQuery("UPDATE ts_tuition_coursecategories SET `{$field}` = `name`, `changed` = `changed` WHERE `{$field}` = ''");
		}

		DB::executeQuery("ALTER TABLE ts_tuition_coursecategories DROP `name`");

		return true;

	}

	private function migrateCourseLanguages() {

		$fields = DB::describeTable('kolumbus_tuition_levelgroups', true);
		if(!isset($fields['title'])) {
			return true;
		}

		Util::backupTable('kolumbus_tuition_levelgroups');

		$languages = (array)Ext_TS_Config::getInstance()->frontend_languages;
		foreach ($languages as $language) {
			$field = 'name_'.$language;
			DB::executeQuery("UPDATE kolumbus_tuition_levelgroups SET `{$field}` = `title`, `changed` = `changed` WHERE `{$field}` = ''");
		}

		DB::executeQuery("ALTER TABLE kolumbus_tuition_levelgroups DROP `name`");
		DB::executeQuery("ALTER TABLE kolumbus_tuition_levelgroups DROP `title`");

		return true;

	}

	private function setCourseCategoriesSort() {

		$language = \Illuminate\Support\Arr::first(Ext_TS_Config::getInstance()->frontend_languages);

		$categories = DB::getQueryRows("SELECT `id` FROM `ts_tuition_coursecategories` WHERE `active` = 1 AND `position` = 0 ORDER BY `name_{$language}`");
		foreach ($categories as $index => $category) {
			$category['position'] = $index + 1;
			DB::executePreparedQuery("UPDATE `ts_tuition_coursecategories` SET `position` = :position, `changed` = `changed` WHERE `id` = :id", $category);
		}

	}

}