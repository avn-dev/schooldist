<?php

class Ext_TS_System_Checks_School_AppSettings2 extends GlobalChecks {

	public function getTitle() {
		return 'Migration of student app settings';
	}

	public function getDescription() {
		return 'Migrate student app settings per school into an exernal app.';
	}

	public function executeCheck() {

		if (!in_array('ts_schools_app_settings', DB::listTables())) {
			return true;
		}

		Util::backupTable('customer_db_2');
		Util::backupTable('ts_schools_app_settings');

		DB::begin(__CLASS__);

		$enableApp = false;
		$data = [];
		$rows = DB::getQueryRows("SELECT * FROM ts_schools_app_settings");
		foreach ($rows as $row) {
			$data[$row['school_id']][$row['key']][$row['additional']] = $row['value'];
		}

		/** @var Ext_Thebing_School[] $schools */
		$schools = Ext_Thebing_School::query()->get();

		foreach ($schools as $school) {

			if (empty($data[$school->id])) {
				continue;
			}

			$this->migratePageSettings($school, (array)$data[$school->id]);
			$this->migrateFields($school, (array)$data[$school->id]['enabled_field']);

			$this->migrateWelcomeTexts($school, (array)$data[$school->id]['welcome_text_title'], 'student_app_welcome_title_');
			$this->migrateWelcomeTexts($school, (array)$data[$school->id]['welcome_text_student'], 'student_app_welcome_text_');

			$this->migrateUpload($school);

			$school->setMeta('student_app_template_forgotten_password', (int)$school->app_forgotten_password_template);

			$school->save();

			$enableApp = true;

		}

		if (
			$enableApp &&
			!\TcExternalApps\Service\AppService::hasApp(TsStudentApp\Handler\ExternalApp::APP_NAME)
		) {
			$app = (new TsStudentApp\Handler\ExternalApp())->setAppKey(TsStudentApp\Handler\ExternalApp::APP_NAME);
			\TcExternalApps\Service\AppService::installApp($app);
		}

		DB::commit(__CLASS__);

		DB::executeQuery("ALTER TABLE customer_db_2 DROP app_forgotten_password_template, DROP app_image");
		DB::executeQuery("DROP TABLE ts_schools_app_settings");

		return true;

	}

	private function migratePageSettings(Ext_Thebing_School $school, array $data) {

		// Altes Konstrukt aus \Ext_Thebing_School_Gui2::getEditDialogHTML()
		$oStudentApp = \TsMobile\Service\App\Student::getBackendInstance($school);
		$aPages = $oStudentApp->getPages();
		$aPageOptions = array();
		foreach($aPages as $sLayer => $aLayerData) {
			foreach($aLayerData['items'] as $sPage => $aPageData) {
				if(
					$sLayer === 'bottom' ||
					$sPage === 'welcome'
				) {
					continue;
				}
				$aPageOptions[$sPage] = $aPageData['title'];
			}
		}
		$aPageOptions['activities'] = \Ext_TC_Placeholder_Abstract::translateFrontend('Aktivitäten', \System::getInterfaceLanguage());

		$disabled = array_keys(array_diff_key($aPageOptions, (array)$data['enabled_page']));

		$school->setMeta('student_app_disabled_pages', $disabled);

	}

	private function migrateFields(Ext_Thebing_School $school, array $fields) {

		$fields = collect($fields)->keys()->transform(function (string $field) {
			$field = str_replace('static_', '', $field);
			if ($field === 'attendance_note') {
				$field = 'attendance_comment';
			}
			return $field;
		});

		$school->setMeta('student_app_enabled_fields', $fields->toArray());

	}

	private function migrateWelcomeTexts(Ext_Thebing_School $school, array $data, string $metaKey) {

		foreach ($data as $iso => $value) {
			$school->setMeta($metaKey.$iso, $value);
		}

	}

	private function migrateUpload(Ext_Thebing_School $school) {

		$image = $school->getSchoolFileDir().'/app/'.$school->app_image;
		if (!is_file($image)) {
			return;
		}

		$tag = FileManager\Entity\Tag::query()
			->where('entity', Ext_Thebing_School::class)
			->where('tag', TsActivities\Entity\Activity::APP_IMAGE_TAG)
			->first();

		if ($tag === null) {
			$tag = new FileManager\Entity\Tag();
			$tag->entity = Ext_Thebing_School::class;
			$tag->tag = TsActivities\Entity\Activity::APP_IMAGE_TAG;
			$tag->save();
		}

		$file = new FileManager\Entity\File();
		$file->entity = Ext_Thebing_School::class;
		$file->entity_id = $school->id;
		$file->file = $school->app_image;
		$file->addJoinTableObject('tags', $tag);

		Util::checkDir($file->getPath());

		if (!rename($image, $file->getPathname())) {
			throw new RuntimeException(sprintf('Could not move file %s to %s', $image, $file->getPathname()));
		}

		$file->save();

		rmdir($school->getSchoolFileDir().'/app');

	}

}
