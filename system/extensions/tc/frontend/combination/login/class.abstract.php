<?php

use Smarty\Smarty;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author Mehmet Durmaz
 */

abstract class Ext_TC_Frontend_Combination_Login_Abstract extends Ext_TC_Frontend_Combination_Abstract
{
	protected $_oMessage;
	protected $_sLanguage = '';
	protected $_sTask;

	public function __call($sFunction, $aArguments)
	{
		if(strpos($sFunction, 'protected') !== false)
		{
			$sFunction = str_replace('protected', '', $sFunction);
			if(
			  method_exists($this, $sFunction)
			)
			{
				$mReturn = call_user_func_array(array($this, $sFunction), $aArguments);
			}
			else
			{
				throw new Exception('Function "'.$sFunction.'" does not exist in "'.get_class($this).'"');
			}
		}
		else
		{
			throw new Exception('Function "'.$sFunction.'" does not exist in "'.get_class($this).'"');
		}

		return $mReturn;
	}

	/**
	 *
	 * @global array $page_data
	 * @param SmartyWrapper $oSmarty
	 * @param Ext_TC_Frontend_Combination $oCombination
	 */
	public function __construct(Ext_TC_Frontend_Combination $oCombination, Smarty $oSmarty = null)
	{
		global $page_data,$session_data;

		parent::__construct($oCombination, $oSmarty);

		$this->_initPublicAccess();

		$sLanguage = $this->_getCombinationItem('language');

		// Interface-Sprache setzen
		$this->_sLanguage = $sLanguage;

		$page_data['language'] = $sLanguage;
		$session_data['language'] = $sLanguage;

		Ext_TC_System::setInterfaceLanguage($sLanguage);

		$this->_oMessage = new Ext_TC_Frontend_Messages($oSmarty);

		$this->_oSmarty->registerObject(
			'this',
			$this,
			array(
				'getView',
				'getNavItem'
			)
		);

	}

	/**
	 * Bereiche wo man ohne uneingeloggt zugreifen darf
	 */
	protected function _initPublicAccess()
	{
		$this->_aPublicAccess = array(
			'sendPassword',
			'requestPassword',
			'changePassword',
			'executeChangePassword',
			'resetPassword'
		);
	}

	/**
	 * User nicht eingeloggt, Login Formular anzeigen
	 */
	protected function _showLogin() {

		//@todo, session killen?
		$this->_assign('iTableId', $this->_getCustomerDbId());
		$this->_setTask('login');
        $oWDValidate = new \WDValidate();
        $oValidator = new \TcFrontend\Service\Validator\Login();

		if (
            $this->_oRequest->exists('logout') === true &&
            $this->_oRequest->get('logout') === 'ok'
        ) {
            $this->_setMessage('Sie wurden erfolgreich ausgeloggt!', 'success');
        }
        //Wenn Login Parameter übergeben und wir hier gelandet sind, wurden falsche Daten eingegeben
        if(
            $this->_oRequest->exists('table_number') &&
            $this->_oRequest->exists('loginmodul') &&
            $this->_oRequest->exists('customer_login_1') &&
            $this->_oRequest->exists('customer_login_3')
        ) {
            $oValidator->setEmail($this->_oRequest->get('customer_login_1'));
            $oValidator->setPassword($this->_oRequest->get('customer_login_3'));
            $oValidator->setWDValidate($oWDValidate);
            $oValidator->setFirstValidate(true);

            if(!$oValidator->isValid()) {
                $this->_oMessage->setMessageAsArray($oValidator->getErrors());
            } else {
                $this->_setError(Ext_TC_Frontend_Messages::ERROR_LOGIN_FAILED);
            }

        }

	}

	/**
	 * Neues PW anfordern
	 */
	protected function _requestPassword(){
		$this->_setTask('requestPassword');
	}

	/**
	 * Neues PW setzen
	 */
	protected function _resetPassword(){

		if(
			isset($this->_aVars['hash']) &&
			!empty($this->_aVars['hash'])
		){
			$iTableID = $this->_getCustomerDbId();
			// Hash prüfen
			$mCheck = Ext_TC_Login_Reminder::checkHash($this->_aVars['hash'], $iTableID);
			if($mCheck !== false){
				// Hash gültig
				$this->_setTask('resetPassword');
				$this->_assign('hash', $this->_aVars['hash']);
			}else{
				$this->_oMessage->setMessage(Ext_TC_Frontend_Messages::ERROR_INVALID_LINK);
			}
		}else{
			$this->_oMessage->setMessage(Ext_TC_Frontend_Messages::ERROR_WRONG_LINK);
		}
	}

	/**
	 * Ändern des Passwords
	 */
	protected function _executeChangePassword(){

		$this->_setTask('executeChangePassword');
		
		$iTableID = $this->_getCustomerDbId();
		$mCheckHash = Ext_TC_Login_Reminder::checkHash($this->_aVars['hash'], $iTableID);

		if(
			isset($this->_aVars['hash']) &&
			!empty($this->_aVars['hash']) &&
			$mCheckHash !== false // Hash nochmal prüfen
		) {

			$this->_assign('hash', $this->_aVars['hash']);
			$sSecurityStatus = $this->_getCombinationItem('password_security');
			if(
				$this->_aVars['new_password'] == $this->_aVars['new_password_repeat']
			){
				$bCheck = Ext_TC_Util::validPass($this->_aVars['new_password'], $sSecurityStatus);

				if($bCheck) {

					$aData = $mCheckHash;


					$oCustomerDB = new Ext_CustomerDB_DB($aData['db_table']);
					$iLoginId = $this->getLoginId($mCheckHash['object_id']);
					$oCustomerDB->updateCustomerField($iLoginId, 'pass', md5($this->_aVars['new_password']));

					// Anfrage löschen
					Ext_TC_Login_Reminder::deleteRequest($this->_aVars['hash']);

					$this->_showLogin();

					$this->_oMessage->setMessage(Ext_TC_Frontend_Messages::INFO_PASSWORD_CHANGE, 'success');

				} else {
					$this->_oMessage->setMessage(Ext_TC_Frontend_Messages::ERROR_PASSWORDSECURE);
				}
			} else {
				$this->_oMessage->setMessage(Ext_TC_Frontend_Messages::ERROR_PASSWORD_MATCH);
			}
		} else {
			$this->_oMessage->setMessage(Ext_TC_Frontend_Messages::ERROR_PASSWORD_CHANGE);
		}
		
	}

	/**
	 * Password senden
	 * Hier waren noch TS spezifische Aufrufe drin die entfernt wurden
	 */
	protected function _sendPassword() {

		$aError = array();

		$sName = $this->_aVars['user'];
		$iTableID = $this->_getCustomerDbId();

		$oCustomerDB = new Ext_CustomerDB_DB($iTableID);
			
		// Erfolgreiches versenden der Mail
		$bSuccess = false;

		// Prüfen ob alle Felder vorhanden sind
		if(
			$oCustomerDB->external_table != '' &&
			$oCustomerDB->external_table_email != '' &&
			$oCustomerDB->external_table_user != '' &&
			$oCustomerDB->external_table_pass != '' &&
			$oCustomerDB->active == 1
		) {

			// Benutzer rausfinden
			$aCustomer = $oCustomerDB->getCustomerByUniqueField('user', $sName);

			if(!empty($aCustomer)) {

				$sEmail = $this->getLoginEmail($aCustomer['contact_id']);
	
				if(
					!empty($sEmail) &&
					Util::checkEmailMX($sEmail)
				) {

					// Eindeutigen Hash generieren um neues PW zu generieren
					$iCount = 0;
					$sHash = '';
					do{
						$iCount++;

						if($iCount == 3000){
							break;
						}

						$sHash = Ext_TC_Util::generateRandomString(32);
					} while (!Ext_TC_Login_Reminder::checkUniqueHash($sHash));

					if(!empty($sHash)){
						
						$sAdditionalQuery = 'task=resetPassword&hash='.$sHash;
						$sLink = $this->getRequestingUrl($sAdditionalQuery);
						// Mail Inhalt
						$oTemplate = new stdClass();
						$sSubject = L10N::t('Passwort vergessen');

						$oMail = new WDMail();
						$oMail->subject = $sSubject;

						if($oTemplate->html) {
							$oMail->html = $sLink;
						} else {
							$oMail->text = $sLink;
						}

						$bSuccess = $oMail->send($sEmail);

						if($bSuccess !== true) {
							$this->_setError(Ext_TC_Frontend_Messages::ERROR_EMAIL_SEND);
						} else {

							// Speichern das eine erinnerungsmail verschickt worden  ist
							$oReminder = new Ext_TC_Login_Reminder();
							$oReminder->hash		= $sHash;
							$oReminder->mail		= $sEmail;
							$oReminder->db_table	= (int)$oCustomerDB->id;
							$oReminder->object_id	= (int)$aCustomer['contact_id'];
							$oReminder->save();

							// Mail schicken erfolgreich
							$this->_setMessage(Ext_TC_Frontend_Messages::INFO_EMAIL_SEND, 'info');

							$bSuccess = true;
						}

					} else {
						$this->_setError(Ext_TC_Frontend_Messages::ERROR_NO_MAIL);
					}

				} else {
					$this->_setError(Ext_TC_Frontend_Messages::ERROR_GENERATE_HASH);
				}
			} else {
				$this->_setError(Ext_TC_Frontend_Messages::ERROR_NO_CUSTOMER);
			}
		} else {
			$this->_setError(Ext_TC_Frontend_Messages::ERROR_DATABASE_CONFIG);
		}

		if($bSuccess) {
			$this->_setTask('sendPassword');
		} else {
			$this->_setTask('requestPassword');
		}

		return $bSuccess;
	}

	protected function _setError($sMessage) {
		$this->_setMessage($sMessage);
	}

	protected function _setMessage($sMessage, $sType='error')
	{
		$sMessage = $this->t($sMessage);

		$this->_oMessage->setMessage($sMessage, $sType);
	}

	/**
	 * Funktion für die Überprüfung ob User eingeloggt ist
	 */
	protected function _isLoggedIn()
	{
		$aUserData = $this->_aUserData;
		if(
			(
				isset($aUserData['id']) &&
                (int)$aUserData['id'] > 0 &&
                isset($aUserData['idTable']) &&
                $aUserData['idTable'] == $this->_getCustomerDbId()
			)||
			$_COOKIE["student_login"] == 1
		) {
			return true;
		}

		return false;
	}

	/**
	 * @TODO Das ist nicht so toll, dass hier parent umgangen wird
	 */
	public function handleRequest() {

		try {

			$oAccessFrontend = Access::getInstance();

			if(
				$oAccessFrontend instanceof Access_Frontend &&
				$oAccessFrontend->checkValidAccess()
			) {
				$this->_aUserData = $oAccessFrontend->getUserData();

				$oAccessFrontend->reworkUserData($this->_aUserData);
				$this->_aUserData['login'] = 1;
			}

			$bIsLoggedIn = $this->_isLoggedIn();
			$sTask = $this->_oRequest->get('task');
			if(
				$bIsLoggedIn ||
				in_array($sTask, $this->_aPublicAccess)
			) {
				
				$this->_setTask($sTask);
				
				parent::handleRequest();
				
			} else {
				$this->_showLogin();
				// Aufruf loggen, da parent nicht aufgerufen wird
				$this->logUsage('_login');
			}

		} catch(Exception $e) {
			print_r($e->getMessage());
		}

	}

	/**
	 * @param $iCustomerId
	 * @return array
	 */
	public function getLoginId($iCustomerId) {
		$sSql = "
			SELECT `id`
			FROM `ts_inquiries_contacts_logins`
			WHERE `contact_id` = :contact_id;
		";

		$aSql['contact_id'] = $iCustomerId;
		$iId = DB::getQueryOne($sSql, $aSql);
		return $iId;
	}

	/**
	 * Task setzen
	 * @param string $sTask
	 */
	protected function _setTask($sTask) {
		$this->_sTask = $sTask;
		$this->_assign('sTask', $sTask);
	}

	public function getView($sKey) {

		$oTemplate = Ext_TC_Frontend_Template::getByKey($sKey);

		try {
			$sResult = $this->_oSmarty->fetch('string:'.$oTemplate->code);
		} catch(Exception $e) {
			$sResult = '';
		}

		return $sResult;
	}

	/**
	 * Liefert die Combinationssprache zurück für die Anzeige
	 * @return type
	 */
	protected function _getLanguage(){
		$sLanguage = $this->_sLanguage;
		return $sLanguage;
	}

	protected function _getUrl($sTask)
	{
		$sUrl = $this->_getBaseUrl().'task='.$sTask;

		return $sUrl;
	}

	protected function _isDev()
	{
		return Ext_TC_Util::isDevSystem();
	}

	protected function _get_file()
	{
		$sBasePath	= Util::getDocumentRoot().'media/secure';
		$sFile		= $this->_aVars['file'];

		$sFilePath = $sBasePath.$sFile;

		if(is_file($sFilePath)){
			$sFileContent = file_get_contents($sFilePath);
			$this->_setHeader('application/pdf', $sFilePath);
			echo $sFileContent;
		}
	}

    protected function _setHeader($sMimeType, $sFileName)
    {
        $oDateHelper = new WDDate();

        if (ob_get_contents()) {
            Sys_Error_Exception::throwError("Header Daten bereits gesendet");
        }
        header('Content-Description: File Transfer');
        if (headers_sent()) {
            Sys_Error_Exception::throwError("Header Daten bereits gesendet");
        }
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: '.$oDateHelper->get(WDDate::DB_TIMESTAMP).' GMT');
        // force download dialog
        if (strpos(php_sapi_name(), 'cgi') === false) {
                header('Content-Type: application/force-download');
                header('Content-Type: application/octet-stream', false);
                header('Content-Type: application/download', false);
                header('Content-Type: '.$sMimeType.'', false);
        } else {
                header('Content-Type: '.$sMimeType.'');
        }
        // use the Content-Disposition header to supply a recommended filename
        header('Content-Disposition: attachment; filename="'.basename($sFileName).'";');
        header('Content-Transfer-Encoding: binary');
    }

	public function getNavItem($aParams) {

		$aItems		= $this->_getNavItems();
		$sTask		= $aParams['task'];

		$aCurrent = array();
		$this->_getCurrentNavItems($aItems, $aCurrent, null);

		if(in_array($sTask, $aCurrent)) {
			$bSelected = true;
		} else {
			$bSelected = false;
		}

		$sNavItem = '';

		if($bSelected) {
			$sNavItem .= '<li class="active">';
		} else {
			$sNavItem .= '<li>';
		}

		$sLink = $this->_getBaseUrl();
		$sLink .= 'task='.$sTask;

		$sNavItem .= '<a href="'.$sLink.'">';
		$sNavItem .= $this->t($aParams['title']);
		$sNavItem .= '</a>';
		$sNavItem .= '</li>';

		return $sNavItem;
	}

	protected function _getCurrentNavItems($aItems, &$aCurrent, $sParent)
	{
		foreach($aItems as $sKey => $mItem)
		{
			if(is_array($mItem))
			{
				$this->_getCurrentNavItems($mItem, $aCurrent, $sKey);
			}
			else
			{
				if($mItem==$this->_sTask)
				{
					if(!empty($sParent))
					{
						$aCurrent[] = $sParent;
					}
					$aCurrent[] = $mItem;
				}
			}
		}
	}

	abstract protected function _getCustomerDbId();

	abstract protected function _getBaseUrl();

	abstract protected function _getNavItems();
	
	abstract protected function getLoginEmail($iLoginId);
	
}
