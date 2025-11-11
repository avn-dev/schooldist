<?php

namespace TsScreen\Service;

class ScreenApp extends \TcExternalApps\Interfaces\ExternalApp {
	
	const APP_NAME = 'ts_screen';
	
	public function getTitle(): string {
		return \L10N::t('Information screens');
	}
	
	public function getDescription(): string {
		return \L10N::t('This app provides content for digital information screens at your school. <ul><li>Different views available: schedule, student list, test results, level changes</li><li>Multiple screens</li><li>Customisable design</li><li>Scheduled display</li><li>Content management in Fidelo</li><li>News ticker</li></ul>');
	}

	public function getCategory(): string {
		return \Ts\Hook\ExternalAppCategories::TUITION;
	}

	public function getIcon() {
		return 'fa fa-television';
	}
	
	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest) {
		
	}

	public function install() {
		
		\WDCache::deleteGroup(\Admin\Helper\Navigation::CACHE_GROUP_KEY);
		
	}

	public function uninstall() {
		
		\WDCache::deleteGroup(\Admin\Helper\Navigation::CACHE_GROUP_KEY);
		
	}
	
}
	
