<?php

class Ext_TC_System_Checks_DeleteComposerFile extends GlobalChecks {

	public function getTitle() {
		return 'Remove deleted composer.json file in TcApi';
	}

	public function getDescription() {
		return 'Remove deleted composer.json file in TcApi';
	}

	/**
	* @return boolean
	*/
	public function executeCheck() {

		$deleteFiles = array(
			'system/bundles/TcApi/composer.json'
		);

		Util::$iDeletedFiles = 0;

		foreach ($deleteFiles as $deleteFile) {
			Util::recursiveDelete(Util::getDocumentRoot().$deleteFile);
		}

		$this->logInfo('deleteFiles', ['deleted_files' => Util::$iDeletedFiles]);

		return true;
	}

}