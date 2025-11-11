<?php

class Ext_TS_System_Checks_Accommodation_Provider_CleanFiles extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Clean files';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Removes unneeded files and directories.';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		$aFiles = array(
			'/system/config/gui2/ts_accommodation_provider_payment_categories.yml',
			'/system/config/gui2/ts_accommodation_provider_payments.yml'
		);

		foreach((array)$aFiles as $sFile) {
			$sFile = Util::getDocumentRoot(false).$sFile;
			Ext_Thebing_Util::recursiveDelete($sFile);
		}

		return true;

	}

}
