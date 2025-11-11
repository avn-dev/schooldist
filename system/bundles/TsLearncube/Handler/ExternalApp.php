<?php

namespace TsLearncube\Handler;

use Core\Handler\SessionHandler as Session;
use GuzzleHttp\Client;
use TsLearncube\Service\LearncubeWebService;

class ExternalApp extends \TcExternalApps\Interfaces\SystemConfigApp {

	const APP_NAME = 'learncube';
	
	const KEY_URL = 'learncube_url';
	
	const KEY_PUBLIC_API = 'learncube_public_api';
	const KEY_PRIVATE_API = 'learncube_private_api';
	#const KEY_COURSES = 'learncube_courses';
	const L10N_PATH = 'TS » Apps » Learncube';

	protected Session $oSession;

	/**
	 * @return string
	 */
	public function getTitle() : string {
		return \L10N::t('Learncube', self::L10N_PATH);
	}

	public function getDescription() : string {
		return \L10N::t('Automatically adds students to Learncube.', self::L10N_PATH);
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
				'title' => 'Learncube URL',
				'key' => self::KEY_URL.'_'.$iSchoolId
			];
			$aConfigKeys[] = [
				'title' => 'API Public Key',
				'key' => self::KEY_PUBLIC_API.'_'.$iSchoolId
			];
			$aConfigKeys[] = [
				'title' => 'API Private Key',
				'key' => self::KEY_PRIVATE_API.'_'.$iSchoolId,
			];
			// Für die erste Version noch irrelevant
//			$aConfigKeys[] = [
//				'title' => \L10N::t('Kurse'),
//				'key' => self::KEY_COURSES.'_'.$iSchoolId,
//				'type' => 'multiple_select',
//				'options' => \Ext_Thebing_School::getInstance($iSchoolId)->getCourseList(),
//			];

		}

		return $aConfigKeys;
	}

	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest): void
	{

		$schools = \Ext_Thebing_Client::getSchoolList(false, 0, true);

		$config = $oRequest->input('config');

		$error = false;
		foreach ($schools as $school) {
			$uri = $config[self::KEY_URL.'_'.$school->id];
			$publicApiKey = $config[self::KEY_PUBLIC_API.'_'.$school->id];
			$privateApiKey = $config[self::KEY_PRIVATE_API.'_'.$school->id];

			// Nur wenn alles ausgefüllt wurde auch die Validierung ausführen
			if(
				!empty($uri) &&
				!empty($publicApiKey) &&
				!empty($privateApiKey)
			) {

				$client = new Client(['base_uri' => $uri]);

				try {

					LearncubeWebService::getTokenRequest(
						$client,
						$publicApiKey,
						$privateApiKey
					);

				} catch (\GuzzleHttp\Exception\ClientException $e) { # ClientException: 404 Not Found / 422 Unproccesable Entity

					$error = true;
					if ($e->getResponse()->getStatusCode() === 422) {
						$errorMessage = \L10N::t('Falsche(r) API Schlüssel (%s)');
					} elseif ($e->getResponse()->getStatusCode() === 404) {
						$errorMessage = \L10N::t('Falsche URL (%s)');
					} else {
						$errorMessage = \L10N::t('Es ist ein Fehler aufgetreten (%s)');
					}

					$oSession->getFlashBag()->add('error', sprintf($errorMessage, $e->getResponse()->getReasonPhrase()));

				}
			}
		}

		if (!$error) {
			parent::saveSettings($oSession, $oRequest);
		}
	}

}