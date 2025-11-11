<?php

namespace TsTeacherLogin\Handler;

class ExternalApp extends \TcExternalApps\Interfaces\SystemConfigApp {

	const APP_NAME = 'teacher_login';

	const KEY_ALLOW_CLASS_WITHOUT_ROOM = 'teacher_login_allow_class_without_room';

	/**
	 * @return string
	 */
	public function getTitle() : string {
		return \L10N::t('Extended teacher portal');
	}

	public function getDescription() : string {
		return \L10N::t('Extended functions for teachers.');
	}

	public function getIcon(): string {
		return 'fas fa-chalkboard-teacher';
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::TUITION;
	}

	public static function getAllowClassWithoutRoom(): string 
	{
		return \System::d(self::KEY_ALLOW_CLASS_WITHOUT_ROOM, '0');
	}

	protected function getConfigKeys(): array
	{
		return [
			[
				'title' => \L10N::t('Klassen ohne Räume erlauben'),
				'key' => self::KEY_ALLOW_CLASS_WITHOUT_ROOM,
				'type' => 'select',
				'default' => '0',
				'options' => [
					'0' => \L10N::t('Nein'),
					'1' => \L10N::t('Ja')
				]
			]
		];
	}

}
