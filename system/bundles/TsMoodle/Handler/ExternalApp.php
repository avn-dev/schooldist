<?php

namespace TsMoodle\Handler;

use Core\Handler\SessionHandler as Session;

class ExternalApp extends \TcExternalApps\Interfaces\SystemConfigApp {

	const APP_NAME = 'moodle';
	
	const KEY_URL = 'moodle_url';
	
	const KEY_ACCESS_TOKEN = 'moodle_access_token';
	const KEY_DEFAULT_PASSWORD = 'moodle_default_password';
	const KEY_CUSTOM_FIELDS = 'moodle_custom_fields';
	const KEY_SYNC_STUDENT_MODE = 'moodle_sync_student_mode';
	const KEY_SYNC_STUDENT_MODE_ALWAYS = 'always';
	const KEY_SYNC_STUDENT_MODE_ALLOCATION = 'with_allocation';

	/**
	 * @var Session
	 */
	protected $oSession;

	/**
	 * @return string
	 */
	public function getTitle() : string {
		return \L10N::t('Moodle');
	}

	public function getDescription() : string {
		return \L10N::t('Automatically adds students to Moodle.');
	}

	public function getIcon(): string {
		return 'fas fa-chalkboard-teacher';
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::TUITION;
	}
	
	protected function getConfigKeys(): array {
		
		$aSchools = \Ext_Thebing_Client::getSchoolList(true);
		
		$aConfigKeys = [];
		
		foreach($aSchools as $iSchoolId=>$sSchool) {

			$aConfigKeys[] = [
				'type' => 'headline',
				'title' => $sSchool
			];
			$aConfigKeys[] = [
				'title' => \L10N::t('Moodle URL'),
				'key' => self::KEY_URL.'_'.$iSchoolId
			];
			$aConfigKeys[] = [
				'title' => \L10N::t('Moodle Token'),
				'key' => self::KEY_ACCESS_TOKEN.'_'.$iSchoolId
			];
			$aConfigKeys[] = [
				'title' => \L10N::t('Synchronisation von Schülern'),
				'key' => self::KEY_SYNC_STUDENT_MODE.'_'.$iSchoolId,
				'type' => 'select',
				'options' => [
					self::KEY_SYNC_STUDENT_MODE_ALLOCATION => \L10N::t('Nur mit Zuweisung'),
					self::KEY_SYNC_STUDENT_MODE_ALWAYS => \L10N::t('Immer')
				]
			];
			$aConfigKeys[] = [
				'title' => \L10N::t('Default password for Moodle users (leave empty if Moodle should create the password and e-mail it to the user).'),
				'key' => self::KEY_DEFAULT_PASSWORD.'_'.$iSchoolId
			];
			$aConfigKeys[] = [
				'title' => \L10N::t('Custom fields (MOODLE_FIELD=FIDELO_FIELD seperated by ";", available fields in Fidelo: "nationality").'),
				'key' => self::KEY_CUSTOM_FIELDS.'_'.$iSchoolId
			];

		}

		return $aConfigKeys;
	}

}