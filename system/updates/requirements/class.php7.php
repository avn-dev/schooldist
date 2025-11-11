<?php

class Updates_Requirements_PHP7 extends Requirement {
	
	public function checkSystemRequirements() {
		
		$mVersion = self::getPHPVersion();

		if(version_compare($mVersion , "7.0.0", '>=')){
			return true;
		} else {
			$this->_aErrors[] = L10N::t('Please update your PHP version to at least 7.0');
			return false;
		}	

	}

}