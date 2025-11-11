<?php

class Access_Frontend extends Access {
	
	protected $_iTableId;
	protected $_sAccessCode;
	
	/**
	 * @var Ext_CustomerDB_DB 
	 */
	protected $_oCustomerDb;
	
	public function checkDirectLogin($iTableId, $sAccessCode) {

		if(
			is_numeric($iTableId) &&
			strlen($sAccessCode) >= 6
		) {
			$this->_iTableId = (int)$iTableId;
			
			$this->_getCustomerDb();

			if($this->_oCustomerDb->allow_accesscode) {
				$aCustomer = $this->_oCustomerDb->getCustomerByUniqueField('accesscode', $sAccessCode);

				if(
					!empty($aCustomer) && 
					$aCustomer['accesscode'] == $sAccessCode
				) {
					// Falls man vorher woanders eingeloggt war müssen die Daten zurückgesetzt werden, ansonsten überschreibt
					// checkAccess() die Daten wieder wenn Cookies vorhanden sind.
					$this->deleteAccessData();

					$this->_sUser = $aCustomer['email'];
					$this->_sPass = $aCustomer['password'];
					$this->_bExecuteLogin = true;
					$this->_iTableId = $this->_oCustomerDb->id;
					$this->_sAccessCode = $aCustomer['accesscode'];
				}
			}

		}
		
	}

	protected function _getCustomerDb() {
		
		if(empty($this->_iTableId)) {
			throw new Exception('No table id!');
		}
		
		// Wenn noch nicht vorhanden
		if(!$this->_oCustomerDb instanceof Ext_CustomerDB_DB) {
			$this->_oCustomerDb = new Ext_CustomerDB_DB($this->_iTableId);
		}
		
	}
	
	public function checkManualLogin($aVars) {

		if(
			$aVars['loginmodul'] == 1
		) {

			$this->_sUser = $aVars['customer_login_1'];
			$this->_sPass = $aVars['customer_login_3'];
			$this->_iTableId = intval($aVars['table_number']);

			$this->_getCustomerDb();

			$this->_bExecuteLogin = true;
			
		}
		
	}
	
	public function executeLogin() {

		if($this->_bExecuteLogin === false) {
			return false;
		}

		$aUser = array();
		$iRows = 0;

		if (
			$this->_sUser != "" && 
			$this->_sPass != ""
		) {

			if($this->_oCustomerDb->external_table) {
				$sSql = "
					SELECT 
						* 
					FROM 
						".$this->_oCustomerDb->db_table." 
					WHERE 
						(
						    `".\DB::escapeQueryString($this->_oCustomerDb->db_email)."` = '".\DB::escapeQueryString($this->_sUser)."' OR
						    `".\DB::escapeQueryString($this->_oCustomerDb->db_user)."` = '".\DB::escapeQueryString($this->_sUser)."' 
						) AND
						`".\DB::escapeQueryString($this->_oCustomerDb->db_active)."` = 1
					";
			} else {
				$sSql = "
					SELECT 
						*, 
						UNIX_TIMESTAMP(last_login) last_login 
					FROM 
						".$this->_oCustomerDb->db_table." WHERE 
						(
							email = '".\DB::escapeQueryString($this->_sUser)."' OR 
							nickname = '".\DB::escapeQueryString($this->_sUser)."'
						) AND
						active = 1
				";
			}

			$aUser = DB::getQueryRow($sSql);

		} else {
			if(function_exists('alert')) {
				alert("Sie haben keinen Zugang! Sie haben nicht alle Felder ausgef&uuml;llt.");
			}

			$_VARS['loginfailed'] = 'fields_missing';
		}

		$aTransfer = array(
			'customer_db' => $this->_oCustomerDb,
			'conditions' => array()
		);
		\System::wd()->executeHook('user_data_frontend_additional_login_conditions', $aTransfer);

		$bConditions = $this->_checkConditions($aUser, $aTransfer['conditions']);

		$bPasswordVerify = false;
		
		// Unverschlüsseltes Passwort

		// Zugriff der Code
		if(
			!empty($this->_sAccessCode) &&
			$aUser[$this->_oCustomerDb->db_accesscode] == $this->_sAccessCode
		) {

			$bPasswordVerify = true;
			
		} elseif(
			$this->_oCustomerDb->db_encode_pw == 0 && 
			$aUser[$this->_oCustomerDb->db_pass] == $this->_sPass
		) {
			$bPasswordVerify = true;
			
		} elseif($this->_oCustomerDb->db_encode_pw == 1) {
			
			// Gehashtes Passwort
			// 
			// Altes MD5 Format
			if(strpos($aUser[$this->_oCustomerDb->db_pass], '$') !== 0) {

				$bPasswordVerify = ($aUser[$this->_oCustomerDb->db_pass] === md5($this->_sPass));

				// Convert into new format
				if($bPasswordVerify === true) {

					$aUpdateUser = [
						$this->_oCustomerDb->db_pass => $this->generatePasswordHash($this->_sPass)
					];						
					DB::updateData($this->_oCustomerDb->db_table, $aUpdateUser, '`id` = '.(int)$aUser['id']);

					self::getLogger()->addInfo('Old md5 password updated', ['id'=>$aUser['id'], 'ip'=>$_SERVER['REMOTE_ADDR']]);

				}

			} else {
				$bPasswordVerify = password_verify($this->_sPass, $aUser[$this->_oCustomerDb->db_pass]);
			}
		}

		if( 
			$bPasswordVerify === true && 
			!empty($aUser) &&
			$bConditions === true
		) {

			$this->_prepareUserData($aUser);
			$this->_bValidAccess = true;

			// Nicht machen bei Kundendatenbank Support Funktion
			if(!$this->_oCustomerDb->external_table) {
				$aSupport = DB::getQueryRow("SELECT id FROM customer_db_support WHERE idUser = ".(int)$aUser[$this->_oCustomerDb->db_id]." AND idTable = ".(int)$this->_iTableId."");
				if(empty($aSupport)) {
					DB::executeQuery("UPDATE ".$this->_oCustomerDb->db_table." SET changed = changed, last_login = NOW() WHERE id = ".(int)$aUser[$this->_oCustomerDb->db_id]."");
				}
			}

			return true;
			
		}
		
		if(function_exists('alert')) {
			alert("Sie haben keinen Zugang! Fehler: falsches Passwort oder unbekannter User!");
		}

		$this->_sLastErrorCode = 'wrong_data';
		
		return false;
	}

	public function generatePasswordHash($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}

	protected function _checkConditions($aUser, $aConditions) {
		
		$bConditions = true;
		
		foreach($aConditions as $sField=>$mValue) {
			if($aUser[$sField] != $mValue) {
				$bConditions = false;
				break;
			}
		}
		
		return $bConditions;
				
	}
	
	protected function _prepareUserData($aUser) {

		$aBuffer = array();
		$aBuffer[''.$this->_iTableId.'|0'] = 1;
		$aBuffer[''.$this->_iTableId.'_0'] = 1;
		$this->_aUserData['groups'] = $aUser['groups'];
		$this->_aUserData['groups'] = (array)explode("|", $this->_aUserData['groups']);
		foreach($this->_aUserData['groups'] as $val) {
			if($val) {
				$aBuffer[''.$this->_iTableId.'|'.$val.''] = 1;
				$aBuffer[''.$this->_iTableId.'_'.$val.''] = 1;
			}
		}
		$this->_aUserData['access'] = $aBuffer;

		$this->_sAccessUser = 'customer_db_'.$this->_iTableId.'_'.$aUser[$this->_oCustomerDb->db_id];

		$this->_aUserData['cms'] = false;
		$this->_aUserData['data'] = $aUser;
		$this->_aUserData['table'] = $this->_oCustomerDb->db_table;
		$this->_aUserData['idTable'] = $this->_iTableId;
		$this->_aUserData['username'] = $aUser[$this->_oCustomerDb->db_user];

	}
	
	/**
	 * Prüft, ob der User eingeloggt ist 
	 */
	public function checkSession($sAccessUser, $sAccessPass) {

		// Prüfung nur bei Frontend-User zulassen
		if(strpos($sAccessUser, 'customer_db_') !== 0) {
			return false;
		}

		$sTmp = str_replace('customer_db_', '', $sAccessUser);
		$aTmp = explode('_', $sTmp);

		if(count($aTmp) == 2) {
			$iUserId = (int)$aTmp[1];
			$this->_iTableId = (int)$aTmp[0];
		}

		$this->_getCustomerDb();

		$aAccess = $this->_checkAccess($sAccessUser, $sAccessPass);

		// Prüft, ob Abfrage erfolgreich
		if(!empty($aAccess)) {

			$aUser = $this->_oCustomerDb->getCustomerByUniqueField('id', $iUserId);

			if(!empty($aUser)) {

				if(
					isset($aAccess['persistent_login']) &&
					$aAccess['persistent_login'] === true
				) {
					$this->setPersistentLogin();
				}

				$this->_prepareUserData($aUser);
				$this->_bValidAccess = true;

				return true;

			}
			
		} else {

			$this->_sLastMessage = "Ihre Sessiondaten sind nicht mehr aktuell. Bitte loggen Sie sich erneut ein.";

		}

		// Cookies entfernen, falls es überhaupt Cookies sind, wenn Login nicht mehr gültig.
		if(
			Core\Handler\CookieHandler::is($this->getPassCookieName()) &&
			Core\Handler\CookieHandler::is($this->getUserCookieName())
		) {
			Core\Handler\CookieHandler::remove($this->getUserCookieName());
			Core\Handler\CookieHandler::remove($this->getPassCookieName());
		}
		
		return false;		
	}

	public function reworkUserData(&$aUserData) {

		$aUserData['id'] = $this->_aUserData['data'][$this->_oCustomerDb->db_id];
		$aUserData['email'] = $this->_aUserData['data'][$this->_oCustomerDb->db_user];

		// execute hooks to modify Frontend User Data
		\System::wd()->executeHook('user_data_frontend', $aUserData);

	}
	
	protected function _getCacheKey() {
		
		$sCacheKey = 'access_frontend_'.$this->_sAccessUser;

		if($this->_oCustomerDb === null) {
			throw new Exception('No customer db!');
		}
		
		if($this->_oCustomerDb->multi_login) {
			$sCacheKey .= '_'.$this->_sAccessPass;
		}

		return $sCacheKey;
		
	}
	
	public function deleteAccessData() {

		parent::deleteAccessData();

	}

	public function checkPersistentLogin(MVC_Request $oRequest) {

		$iValue = $oRequest->get('customer_login_remember_password', '1');

		if($iValue === 'on') {
			$this->setPersistentLogin();
		}

	}

	public function setPersistentLogin() {

		// Login-Cookies eine Woche merken
		$this->iCookieExpire = time()+(24*60*60*7);

	}


}
