<?php

class Updates_Requirements_PHP81 extends Requirement {
	
	public function checkSystemRequirements() {

		if(version_compare(PHP_VERSION , "8.1.0", '>=')){
			return true;
		}

		$this->_aErrors[] = L10N::t('Please update your PHP version to at least 8.1');
		return false;
	}

}