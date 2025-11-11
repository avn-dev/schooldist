<?php

class Ext_Thebing_System_Checks_System_Languagefields extends GlobalChecks {

	public function getTitle() {
		return 'Update language fields';
	}

	public function getDescription() {
		return 'Check and update custom internationalization and localization of resources.';
	}

	public function executeCheck() {

		Ext_Thebing_Util::updateLanguageFields();

		return true;

	}

}