<?php
//Blockiert das System ;)
class Ext_Thebing_System_Checks_Block extends GlobalChecks {

	public function isNeeded(){
		global $user_data;

		if(
			$user_data['name'] == 'admin' ||
			$user_data['name'] == 'wielath' ||
			$user_data['name'] == 'koopmann' ||
			$user_data['name'] == 'clicred' ||
			$user_data['name'] == 'patrick' ||
			$user_data['name'] == 'alpha'
		) {
			return false;
		}
		
		return true;
	}

	public function executeCheck(){
		global $user_data, $system_data;

		$this->_aFormErrors[] = 'The system is currently not available. Please try again later!';

		return false;

	}

}


?>