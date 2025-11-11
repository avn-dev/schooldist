<?php

namespace TsAccommodationLogin\Handler;

use Core\Factory\ValidatorFactory;
use Core\Handler\SessionHandler;
use TcExternalApps\Service\AppService;

class ExternalApp extends \TcExternalApps\Interfaces\SystemConfigApp {

	const APP_NAME = 'accommodation_login';

	const KEY_COLUMNS = 'accommodation_login_columns';

	const KEY_ONE_LANGUAGE = 'accommodation_login_one_language';

	const KEY_HIDE_FAMILY_DESCRIPTION = 'accommodation_login_hide_family_description';

	const KEY_HIDE_WAY_DESCRIPTION = 'accommodation_login_hide_way_description';

	const KEY_TEMPLATE = 'accommodation_login_template';

	const KEY_DEACTIVATED_PAGES = 'accommodation_login_deactivated_pages';

	/**
	 * @return string
	 */
	public function getTitle() : string {
		return \L10N::t('Accommodation portal');
	}

	public function getDescription() : string {
		return \L10N::t('Login area for accommodation providers.');
	}

	public function getIcon() {
		return 'fas fa-bed';
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::ACCOMMODATION;
	}

	protected function getConfigKeys(): array {

		$aTemplates = \Ext_TC_Communication_Template::getSelectOptions('mail', [
			'application' => 'accommodation_resources_provider'
		])->forget(0)->toArray();

		return [
			[
				'title' => \L10N::t('Optionale Spalten'),
				'key' => self::KEY_COLUMNS,
				'type' => 'multiple_select',
				// Default = alle Spalten
				'default' => $this->getDefaultColumnValues(),
				'options' => $this->getAllAvailableColumns(),
			],
			[
				'title' => \L10N::t('Deaktivierte Seiten'),
				'key' => self::KEY_DEACTIVATED_PAGES,
				'type' => 'multiple_select',
				'options' => $this->getAllPages(),
			],
			[
				'title' => \L10N::t('Beschreibungsfelder nur in der Schulsprache anzeigen'),
				'key' => self::KEY_ONE_LANGUAGE,
				'type' => 'checkbox'
			],
			[
				'title' => \L10N::t('Familienbeschreibung im Portal ausblenden'),
				'key' => self::KEY_HIDE_FAMILY_DESCRIPTION,
				'type' => 'checkbox'
			],
			[
				'title' => \L10N::t('Wegbeschreibung im Portal ausblenden'),
				'key' => self::KEY_HIDE_WAY_DESCRIPTION,
				'type' => 'checkbox'
			],
			[
				'title' => \L10N::t('Vorlage für "Passwort vergessen"-E-Mail'),
				'key' => self::KEY_TEMPLATE,
				'type' => 'select',
				'options' => \Ext_TC_Util::addEmptyItem($aTemplates)
			]
		];

	}

	public function getAllAvailableColumns() {
		return [
			'image' => \L10N::t('Image'),
			// Fullname wird immer angezeigt
			'period' => \L10N::t('Period'),
			'school' => \L10N::t('School'),
			'courses' => \L10N::t('Courses'),
			'accommodation' => \L10N::t('Accommodation'),
			'wishes' => \L10N::t('Wishes'),
			'arrival' => \L10N::t('Arrival / Departure'),
			'arrival_with_details' => \L10N::t('Arrival / Departure with transfer provider details'),
		];
	}

	public function getDefaultColumnValues() {
		return array_keys($this->getAllAvailableColumns());
	}

	public function getAllPages() {
		return [
			// Dashboard nicht deaktivierbar (immer anzeigen)
			'profile' => \L10N::t('Profile'),
			'availabillity_requests' => \L10N::t('Availabillity Requests'),
			'payments' => \L10N::t('Payments'),
		];
	}

	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest)
	{
		// Abgeleitet, da in der Parent der Request und nicht getConfigKeys() durchgegangen wird (gelöschte Werte werden
		// da nicht gespeichert / gelöscht.
		$config = $oRequest->input('config', []);
		$dbConfig = \Ext_TS_Config::getInstance();

		foreach ($this->getConfigKeys() as $configKey) {
			$key = $configKey['key'];
			$userInputValue = $config[$key];
			if (
				$key == self::KEY_COLUMNS &&
				empty($userInputValue)
			) {
				// Sonst ist der Wert null in der DB und null heißt Default also alles wieder.
				$userInputValue = ['NaN'];
			}
			$dbConfig->set($key, $userInputValue);
		}
	}
}
