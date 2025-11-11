<?php

class Ext_TS_System_Checks_CleanFiles extends GlobalChecks {

	public function getTitle() {
		return 'Delete old files';
	}

	public function getDescription() {
		return 'Delete unneeded files.';
	}

	public function executeCheck() {
		
		$aDelete = [
			'system/bundles/TsCanvas/Resources/config/composer.json',
			'system/bundles/TsCanvas/Resources/config/routes.yml'
		];
		
		Util::$iDeletedFiles = 0;
		
		foreach($aDelete as $sDelete) {
			Util::recursiveDelete(Util::getDocumentRoot().$sDelete);
		}

		$this->logInfo('deleteFiles', array('deleted_files'=>Util::$iDeletedFiles));
		
		return true;
	}

}
