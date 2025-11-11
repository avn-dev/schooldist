<?php

namespace TsScreen\Hook;

class NavigationLeftHook extends \Core\Service\Hook\AbstractHook {
	
	const TRANSLATION_PATH = 'TS » Digital Screens';
	
	public function run(array &$mixInput) {

		if(
			\TcExternalApps\Service\AppService::hasApp(\TsScreen\Service\ScreenApp::APP_NAME) &&
			$mixInput['name'] === 'ac_admin'
		) {
			$mixInput['childs'][] = [
				\L10N::t('Digital screens', self::TRANSLATION_PATH),
				'/gui2/page/TsScreen_list',
				0,
				'',
				null,
				'ts.admin.frontend.digital_screens'
			];
		}

	}
	
}

