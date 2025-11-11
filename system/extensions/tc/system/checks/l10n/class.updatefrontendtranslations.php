<?php

class Ext_TC_System_Checks_L10N_UpdateFrontendTranslations extends GlobalChecks {

	public function getTitle() {
		return 'Update Frontend Translations';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		if(Ext_TC_Util::getSystem() === 'school') {

			if(
				Ext_TC_Util::isDevSystem() ||
				Ext_TC_Util::isTestSystem()
			) {
				$oUpdate = new Ext_Thebing_Update('test');
				#return true;
			} elseif(Ext_TC_Util::isLive2System()) {
				$oUpdate = new Ext_Thebing_Update('test');
			} else {
				$oUpdate = new Ext_Thebing_Update('live');
			}

			$oUpdate->updateFrontendTranslations();

		} else {
			throw new RuntimeException('Currently not compatible with TA!');
		}

		return true;

	}

}