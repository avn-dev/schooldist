<?php
namespace TsMobile\Controller;

use TsMobile\Generator\Navigation;
use TsMobile\Generator\PageHandler;

class ApiController extends AbstractController {

	/**
	 * Meldet den Benutzer am System an uns setzt die Zugriffsdaten in den Cache für 
	 * spätere Authentifizierung
	 */
	public function postLoginAction() {

		// Übersetzungen hinzufügen, da es den AllData-Request beim direkten App-Start nicht mehr gibt
		$this->getTranslationsAction();

		$this->_checkAppType();
		
		$sUsername = $this->_oRequest->get('username');
		$sPassword = $this->_oRequest->get('password');
				
		if(
			empty($sUsername) ||
			empty($sPassword)
		) {
			// Wenn keine Eingabedaten vorhanden wird eine Fehlermeldung generiert
			$this->set('error', 'fields_missing');
			return;
		}

		$this->_handleLoginAction($sUsername, $sPassword);
	}

	public function postCodeLoginAction() {
		
		$this->_checkAppType();

		$sAccessCode = $this->_oRequest->get('access_code');
				
		if(empty($sAccessCode)) {
			// Wenn keine Eingabedaten vorhanden wird eine Fehlermeldung generiert
			$this->set('error', 'fields_missing');
			return;
		}

		$sAppType = $this->_oRequest->get('app_type');

		$this->_oAccessFrontend->checkDirectLogin($this->_aApplications[$sAppType], $sAccessCode);

		if(
			$this->_oAccessFrontend->checkExecuteLogin() === true &&
			$this->_oAccessFrontend->executeLogin() === true
		) {
			/**
			 * Der Part ist redundant zu _handleLoginAction(). Aber da wir eh bald die neue App haben fällt das alles weg
			 */

			$sLoginKey = $this->_saveAccessData();

			$this->set('key', $sLoginKey);
			$this->set('user', $this->_oAccessFrontend->getAccessUser()); // Besteht aus customer_db + Tabellen-ID + User-ID

			// Params setzen, damit checkAuth() im Login-Request korrekt funktioniert (wird für AllData benötigt)
			$this->_oRequest->add(array(
				'login_user' => $this->get('user'),
				'login_key' => $this->get('key')
			));

			// AllData-Request beim Login mitschicken
			$this->getAllDataAction();

			return true;

		} else {
			$this->set('error', 'wrong_data');
		}

		/*$aUserData = $this->_oApp->getUserDataByAccessCode(md5($sAccessCode));

		if(
			!empty($aUserData) &&
			!empty($aUserData['username']) &&
			!empty($aUserData['password'])
		) {
			// Login ausführen
			$this->_oAccessFrontend->bEncodePassword = false;
			$this->_handleLoginAction($aUserData['username'], $aUserData['password']);
			$this->_oAccessFrontend->bEncodePassword = true;

			// Access-Code wurde erfolgreich benutzt, daher löschen
			// Da beim PW-Reset das alte PW abgefragt wird, kann der Schüler nie wieder sein PW zurücksetzen
			//$this->_oApp->saveAccessKey('');

		} else {
			$this->set('error', 'wrong_data');
		}*/
	}
	
	protected function _handleLoginAction($sUsername, $sPassword) {
		
		$sAppType = $this->_oRequest->get('app_type');
		
		// Login
		$this->_oAccessFrontend->checkManualLogin(array(
			'customer_login_1' => $sUsername,
			'customer_login_3' => $sPassword,
			'table_number' => $this->_aApplications[$sAppType],
			'loginmodul' => 1
		));

		// Prüfen, ob die Eingabedaten richtig sind
		$bAccess = $this->_oAccessFrontend->executeLogin();

		if($bAccess) {

			$sLoginKey = $this->_saveAccessData();

			$this->set('key', $sLoginKey);
			$this->set('user', $this->_oAccessFrontend->getAccessUser()); // Besteht aus customer_db + Tabellen-ID + User-ID

			// Params setzen, damit checkAuth() im Login-Request korrekt funktioniert (wird für AllData benötigt)
			$this->_oRequest->add(array(
				'login_user' => $this->get('user'),
				'login_key' => $this->get('key')
			));

			// AllData-Request beim Login mitschicken
			$this->getAllDataAction();

			return true;
			
		} else {
			$this->set('error', 'wrong_data');
		}

		return false;
	}
	
	public function postForgotPasswordAction() {
		
		$this->_checkAppType();
		
		$sEmail	= $this->_oRequest->get('email');
		
		if(empty($sEmail)) {
			// Wenn keine Eingabedaten vorhanden wird eine Fehlermeldung generiert
			$this->set('error', 'fields_missing');
			return;
		}
		
		if(\Util::checkEmailMx($sEmail) === false) {
			$this->set('error', 'wrong_data');
			return;
		}

		// TODO: Wenn es kein Login-Objekkt in der Datenbank gibt, ist das Zurücksetzen unmöglich
		$oUser = $this->_oApp->verifyUserByEmail($sEmail);
		if($oUser === null) {
			$this->set('error', 'wrong_data');
			return;
		}
		
		$this->_oApp->setUser($oUser);
		
		$sAccessCode = $this->_generateAccessCode();
		
		$bSuccess = $this->_oApp->saveAccessKey($sAccessCode);
		
		if($bSuccess) {
			// E-Mail versenden
			$bSend = $this->_oApp->sendAccessCode($sEmail, $sAccessCode);

			if($bSend !== false) {
				$this->set('access_code', true);
			} else {

				$oMail = new \WDMail();
				$oMail->subject = 'TsMobile API: Anforderung von Passwort, aber kein Template konfiguriert – '.\System::d('domain');
				$oMail->text = print_r($this->_oRequest, 1);
				$oMail->send(array('TsMobile@p32.de'));

				$this->set('error', 'unknown_error');
			}
		} else {
			$this->set('error', 'unknown_error');
		}
	}

	public function postChangePasswordAction() {
		
		if(!$this->checkAuth()) {
			$this->set('error', 'no_access');
			return;
		}
		
		$sPassword1	= $this->_oRequest->get('password1');
		$sPassword2	= $this->_oRequest->get('password2');
		$sPasswordOld	= $this->_oRequest->get('password_old');
		
		if(
			empty($sPassword1) ||
			empty($sPassword2) ||
			empty($sPasswordOld)
		) {
			$this->set('error', 'fields_missing');
			return;
		}
		
		if($sPassword1 != $sPassword2) {
			$this->set('error', 'no_matching_passwords');
			return;
		}
		
		if($this->_oApp->checkPassword($sPasswordOld)) {
			if($this->_oApp->changePassword($sPassword1)) {			
				$this->set('success', $this->_oApp->t('Change password successfully'));
			} else {
				$this->set('error', 'unknown_error');
			}
		} else {
			$this->set('error', 'wrong_data');
		}
		
	}
	
	/**
	 * Liefert alle Daten für die App
	 */
	public function getAllDataAction() {

		$bAuth = $this->checkAuth();

		$this->set('authenticated', $bAuth);

		$this->getTranslationsAction();

		// Wenn der Benutzer bereits eingeloggt ist, werden alle Daten geladen
		if($bAuth) {
			$this->getNavigationAction();
			$this->getAllPagesAction();
		}
	}
	
	/**
	 * Meldet den Benutzer am System ab und löscht die Zugriffsdaten aus dem 
	 * Cache
	 */
	public function getLogoutAction() {

		if(!$this->checkAuth()) {
			$this->set('error', 'no_access');
			return;
		}

		$this->_oAccessFrontend->deleteAccessData();
	}

	/**
	 * Übergibt dem View die Menüpunkte für die App
	 */
	public function getNavigationAction() {
		
		if(!$this->checkAuth()) {
			$this->set('error', 'no_access');
			return;
		}
						
		$oNavigation = new Navigation($this->_oApp);

		$this->set('navigation', $oNavigation->generate());
	}
	
	/**
	 * setzt die Daten der angeforderten Seite
	 */
	public function getPagesAction() {
		
		if(!$this->checkAuth()) {
			$this->set('error', 'no_access');
			return;
		}

		$sPage = $this->_oRequest->get('page');
		
		$aPageData = $this->_getPageData($sPage);
		$this->set($sPage, $aPageData);
	}
		
	/**
	 * Fügt die für die App benötigten Übersetzungen hinzu
	 */
	public function getTranslationsAction() {

		$aTranslations = array(
			'username' => $this->_oApp->t('Username'),
			'password' => $this->_oApp->t('Password'),
			'forgot_password' => $this->_oApp->t('Forgot password'),
			'remember_me' => $this->_oApp->t('Remember me?'),
			'login' => $this->_oApp->t('Login'),
			'email' => $this->_oApp->t('E-Mail'),			
			'empty_user_credentials' => $this->_oApp->t('Please fill in username and password.'),
			'wrong_user_credentials' => $this->_oApp->t('Invalid username or password.'),
			'unknown_error' => $this->_oApp->t('Unknown error'),
			'no_access' => $this->_oApp->t('No access!'),
			'loading_data' => $this->_oApp->t('Loading data…'),
			'no_connection' =>  $this->_oApp->t('Could not connect to server.'),
			'go_back' => $this->_oApp->t('Back'),

			'get_code_description' => $this->_oApp->t('Fill in your e-mail-address and you will get an access code for login'),
			'get_code' => $this->_oApp->t('Get access code'),
			'input_code' => $this->_oApp->t('Input access code'),
			'empty_email_credentials' => $this->_oApp->t('Please fill in your e-mail-address.'),
			'wrong_email_credentials' => $this->_oApp->t('Invalid e-mail-address.'),
			'verify' => $this->_oApp->t('Verify'),
			'access_code' => $this->_oApp->t('Access Code'),
			'insert_code_description' => $this->_oApp->t('An access code was sent to you by e-mail. Please fill in to get logged in.'),
			'empty_access_code_credentials' => $this->_oApp->t('Please fill in access code'),
			'wrong_access_code_credentials' => $this->_oApp->t('Invalid access code'),
			
			'password_change_description' => $this->_oApp->t('Use this form to change your password!'),
			'password2' => $this->_oApp->t('Repeat password'),
			'password_old' => $this->_oApp->t('Confirm with old password'),
			'change_password' => $this->_oApp->t('Change password'),
			'empty_change_password_credentials' => $this->_oApp->t('Please fill in all data'),
			'no_matching_passwords' => $this->_oApp->t('The passwords do not match'),
			'wrong_password_credentials' => $this->_oApp->t('Invalid password')
		);

		$this->set('translations', $aTranslations);
	}

	/**
	 * Lädt die Daten aller Seiten der App
	 */
	public function getAllPagesAction() {		
		$aPages = $this->_oApp->getPages();
		$aPages = $this->_oApp->filterPages($aPages);
		
		$aIgnoreTypes = array('logout');
		
		$aPageData = array();
		
		foreach($aPages as $aItem) {
			$aPageKeys = array_keys((array)$aItem['items']);
			if(!empty($aPageKeys)) {
				// Für alle Page-Keys _getPageData() aufrufen
				foreach($aPageKeys as $sPage) {
					if(in_array($sPage, $aIgnoreTypes)) {
						continue;
					}					
					$aTempPage = $this->_getPageData($sPage);
					$aPageData[$sPage] = $aTempPage;
				}
			}
		}	
		
		$this->set('pages', $aPageData);
	}

	/**
	 * Lädt die angeforderte Page und setzt HTML und Storage-Daten
	 *
	 * @param $sPage
	 * @return array
	 */
	protected function _getPageData($sPage) {

		$oPageHandler = new PageHandler($this->_oApp, $sPage);
		$oPage = $oPageHandler->getPage();

		$aFiles = (array)$this->get('files');
		$aStorage = array();
		$sHtml = '';

		if($oPage) {
			$aStorage = (array) $oPage->getStorageData();
			$aFiles	= array_merge($aFiles, $oPage->getFileData());
			$sHtml = $oPage->render();
		}

		$this->set('files', $aFiles);

		return array(
			'html' => $sHtml,
			'storage' => $aStorage
		);
	
	}
	
	/**
	 * Generiert einen 6-stelligen alpha-nummerischen Code
	 * 
	 * @return string
	 */
	protected function _generateAccessCode() {
		$sAccessCode = \Util::generateRandomString(6);
		return $sAccessCode;
	}
}
