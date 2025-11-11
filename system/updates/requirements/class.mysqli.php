<?php

class Updates_Requirements_Mysqli extends Requirement {
	
	public function checkSystemRequirements() {
		
		if(!DB::checkMysqli()){
			$this->_aErrors[] = L10N::t('Please install PHP Mysqli');
			return false;
		}
		
		return true;
	}
}
	