<?php

class Updates_Requirements_PHP83 extends Requirement {
	
	public function checkSystemRequirements() {

		if(version_compare(PHP_VERSION , "8.3.0", '>=')){
			return true;
		}

		$this->_aErrors[] = 'This update requires a newer version of the server software (PHP 8.3). Please contact our support team for help. You don\'t need to do anything yourself — we\'ll take care of it!';
		return false;
	}

}