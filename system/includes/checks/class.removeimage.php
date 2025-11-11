<?php

class Checks_RemoveImage extends GlobalChecks {

	public function getTitle() {
		return 'Remove old image script';
	}
	
	public function getDescription() {
		return '';
	}
	
	/**
	 * @return boolean
	 */
	public function executeCheck() {

		$aDelete = array(
			'public/image.php'
		);

		Util::$iDeletedFiles = 0;
		
		foreach($aDelete as $sDelete) {
			Util::recursiveDelete(Util::getDocumentRoot().$sDelete);
		}

		$this->logInfo('deleteFiles', array('deleted_files'=>Util::$iDeletedFiles));

		return true;
	}

}