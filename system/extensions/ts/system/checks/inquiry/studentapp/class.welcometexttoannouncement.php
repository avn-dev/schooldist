<?php

class Ext_TS_System_Checks_Inquiry_StudentApp_WelcomeTextToAnnouncement extends \GlobalChecks
{
	public function getTitle()
	{
		return 'Student App';
	}

	public function getDescription()
	{
		return 'Converts student welcome text to new structure';
	}

	public function executeCheck()
	{
		if (
			!\TcExternalApps\Service\AppService::hasApp(\TsStudentApp\Handler\ExternalApp::APP_NAME) ||
			$this->getExistingAnnouncements()->isNotEmpty()
		) {
			return true;
		}

		$backup = [
			\Util::backupTable('ts_student_app_contents'),
			\Util::backupTable('ts_student_app_contents_i18n')
		];

		if (in_array(false, $backup)) {
			__pout('Backup error');
			return false;
		}

		\DB::begin(__METHOD__);

		try {
			
			$welcomeTexts = $this->getWelcomeTexts();

			foreach ($welcomeTexts as $schoolId => $languages) {
				$school = Ext_Thebing_School::getInstance($schoolId);
				$appImage = $school->getFirstFile('App-Image');

				$school->setMeta('student_app_home_boxes', ['next-events', 'last-messages', 'announcements', 'activity-advertisement']);
				$school->save();

				$img = ($appImage) ? $appImage->getPublicUrl() : null;

				$appContent = new \TsStudentApp\Entity\AppContent();
				$appContent->released = 1;
				$appContent->school_id = $schoolId;
				$appContent->type = \TsStudentApp\Enums\AppContentType::ANNOUNCEMENT->value;

				$i18n = [];
				foreach ($languages as $language => $data) {

					if (!empty($img)) {
						$data['text'] = '<img src="https://'.\Util::getHost().$img.'" style="width: 100%"/>'.$data['text'];
					}

					$i18n[] = [
						'language_iso' => $language,
						'title' => (string)$data['title'],
						'content' => (string)$data['text'],
					];
				}
				$appContent->i18n = $i18n;
				$appContent->save();
			}

		} catch (\Throwable $e) {
			\DB::rollback(__METHOD__);
			__pout($e);
			return false;
		}

		\DB::commit(__METHOD__);

		return true;
	}

	private function getExistingAnnouncements(): \Illuminate\Support\Collection {
		return \TsStudentApp\Entity\AppContent::query()
			->where('type', \TsStudentApp\Enums\AppContentType::ANNOUNCEMENT)
			->get();
	}

	private function getWelcomeTexts(): array {
		
		$schools = \Ext_Thebing_School::query()->get();

		$languages = \TsStudentApp\Gui2\Data\AppContent::getLanguages();

		$texts = [];
		foreach ($schools as $school) {
			foreach ($languages as $language) {
				if (!empty($text = $school->getMeta('student_app_welcome_text_'.$language['iso']))) {
					$texts[$school->id][$language['iso']]['title'] = $school->getMeta('student_app_welcome_title_'.$language['iso']);
					$texts[$school->id][$language['iso']]['text'] = $text;
				}
			}
		}

		return $texts;
	}

}