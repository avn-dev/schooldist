<?php

class Checks_Htaccess extends GlobalChecks {

	/**
	 * @return boolean
	 */
	public function executeCheck() {

		$oHtaccessGenerator = new \Core\Generator\Htaccess();
		$mWrite = $oHtaccessGenerator->run();

		if($mWrite === false) {
			return false;
		} else {
			return true;
		}

	}
	
	public function getTitle() {
		return 'Update .htaccess file';
	}
	
	public function getDescription() {
		return 'Updates .htaccess file for Apache webserver.';
	}
	
}
