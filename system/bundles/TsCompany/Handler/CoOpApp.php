<?php

namespace TsCompany\Handler;

use Admin\Helper\Navigation;

class CoOpApp extends \TcExternalApps\Interfaces\ExternalApp {

	const APP_NAME = 'coop';

	public function getTitle(): string {
		return \L10N::t('Co-Op');
	}

	public function getDescription(): string {
		return \L10N::t('Co-Op - Beschreibung');
	}

	public function getIcon(): string {
		return 'fas fa-briefcase';
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::TUITION;
	}

	public function install() {
		// Wegen Hooks und Menüpunkte
		\Core\Facade\Cache::forget('core_system_elements');
		\WDCache::deleteGroup(Navigation::CACHE_GROUP_KEY);
	}

	public function uninstall() {
		// Wegen Hooks und Menüpunkte
		\Core\Facade\Cache::forget('core_system_elements');
		\WDCache::deleteGroup(Navigation::CACHE_GROUP_KEY);
	}

}
