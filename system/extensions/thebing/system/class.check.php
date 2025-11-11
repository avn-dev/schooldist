<?

// Checkklasse welche PRO MANDANT den check durchführt!
class Ext_Thebing_System_Check extends GlobalChecks {

	public function isNeeded(){

		if($this->checkClient()){
			return false;
		}
		return true;
	}

	public function modifyCheckData(){
		global $user_data;

		$oClient = new Ext_Thebing_Client($user_data['client']);
		$aMaster = $oClient->getMasterUser();

		if($aMaster['id'] != $user_data['id'] && empty($this->_aFormErrors)){
			$this->_aFormErrors[] = 'Only your master user has access!';
			$this->bError = true;
			return false;
		}


	}

	public function checkClient(){
		global $user_data;
		$sSql = " SELECT
						*
					FROM
						`kolumbus_system_checks`
					WHERE
						`check_id` = :check_id AND
						`client_id` = :client_id AND
						`status` = 1";
		$aSql = array(
						'check_id' =>(int)$this->_aCheck['id'],
						'client_id' =>(int)$user_data['client']);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		if(empty($aResult)){
			return false;
		}
		return true;
	}

	public function saveClient(){
		global $user_data;
		$sSql = " INSERT INTO
						`kolumbus_system_checks`
					SET
						`check_id` = :check_id,
						`client_id` = :client_id,
						`status` = 1";
		$aSql = array(
						'check_id' => (int)$this->_aCheck['id'],
						'client_id' =>(int)$user_data['client']);
		DB::executepreparedQuery($sSql, $aSql);
	}

	public function executeCheck(){
		global $user_data, $_VARS;

		$oClient = new Ext_Thebing_Client($user_data['client']);
		if($oClient->id <= 0){
			$this->_aFormErrors[] = 'No Client Data!';
		}
		// Check zum client speichern damit es nichte rneut gestartet wird
		$this->saveClient();

		return false;

	}

	protected function prepareExecuteCheck(){
		global $_VARS;
		// If no Check found, dont try to execute
		// If you land on this Method and it dosn´t exist an Check ist must give an Error in your Script!
		if(empty($this->_oUsedClass->_aCheck)){
			$this->_oUsedClass->_aFormErrors[] = L10N::t('No Check Found!');
			return false;
		}

		// if after sending the Form the Check is has Changed trow an Error
		// ( e.g 2 Users make the check to the same time an it give an second Check )
		if($_VARS['check_id'] != $this->_oUsedClass->_aCheck['id']){
			$this->_oUsedClass->_aFormErrors[] = L10N::t('You send Data for an other Check, please go back');
			return false;
		}

		$this->_oUsedClass->executeCheck();

		return true;
	}

	public function updateCheck(){

	}

}
