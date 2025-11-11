<?php

class Updates_Requirements_PHP53 extends Requirement {
	
	public function checkSystemRequirements() {
		
		if(!Ext_TC_Util::checkPHP53()){
			$this->_aErrors[] = L10N::t('Please update your PHP version to at least 5.3');
			return false;
		}
		
		return true;
	}
}
	