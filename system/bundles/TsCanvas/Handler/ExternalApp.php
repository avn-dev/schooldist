<?php

namespace TsCanvas\Handler;

use Core\Handler\SessionHandler as Session;

class ExternalApp extends \TcExternalApps\Interfaces\SystemConfigApp {

	const APP_NAME = 'canvas';
	
	const KEY_URL = 'canvas_url';
	
	const KEY_ACCESS_TOKEN = 'canvas_access_token';
	
	/**
	 * @var Session
	 */
	protected $oSession;

	/**
	 * @return string
	 */
	public function getTitle() : string {
		return \L10N::t('Canvas');
	}

	public function getDescription() : string {
		return \L10N::t('Canvas - Beschreibung');
	}

	public function getIcon(): string {
		return 'fas fa-chalkboard-teacher';
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::TUITION;
	}
	
	protected function getConfigKeys(): array {
		
		return [
			[
				'title' => \L10N::t('Canvas URL'),
				'key' => self::KEY_URL,
				'placeholder' => \L10N::t('z.B. https://your_domain.instructure.com/api/v1')
			],
			[
				'title' => \L10N::t('Canvas Token'),
				'key' => self::KEY_ACCESS_TOKEN
			]
		];
		
	}

}