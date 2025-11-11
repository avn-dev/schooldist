<?php

namespace TsMobile\Service;

use \TsMobile\Controller\AbstractController;
use TsMobile\Controller\ApiController;

abstract class AbstractApp {

	/**
	 * @var \TsMobile\Controller\AbstractController
	 */
	protected $_oController = null;

	/**
	 * @var array
	 */
	protected $aParameters = array();

	/**
	 * @var \Ext_Thebing_Teacher|\Ext_TS_Inquiry_Contact_Abstract
	 */
	protected $_oUser = null;

	/**
	 * @var \Ext_Thebing_School 
	 */
	protected $_oSchool = null;

	/**
	 * @var string 
	 */
	protected $_sType = '';

	/**
	 * @var string
	 */
	protected $sInterfaceLanguage = null;

	/**
	 * @var string|null
	 */
	protected $sVersion = null;
	
	/**
	 * Konstruktor
	 * 
	 * @param \TsMobile\Controller\AbstractController $oController
	 */
	public function __construct(AbstractController $oController) {
		$this->_oController = $oController;

		$oRequest = $this->_oController->getRequest();
		if($oRequest instanceof \MVC_Request) {
			$this->sVersion = $oRequest->get('app_version');
		}
	}

	/**
	 * Liefert die Version der App aus dem Request
	 * @return string
	 */
	public function getVersion() {
		return $this->sVersion;
	}

	/**
	 * Liefert alle Menüpunkte der App
	 * 
	 * @return array
	 */
	public function getPages() {

		$sTitle = '';
		if(is_object($this->_oUser)) {
			$sTitle = $this->_oUser->getName();
		}

		$aPages = array(
			'top' => array(
				'title' => $sTitle,
				'items' => array(
					'welcome' => array(
						'title' => $this->t('Welcome'),
						'class' => '\\TsMobile\\Generator\\Pages\\Welcome',
						'type' => 'html'
					),
					'timetable' => array(
						'title' => $this->t('Timetable'),
						'class' => '\\TsMobile\\Generator\\Pages\\Timetable',
						'type' => 'select_list' // Aktuell select_list, sollte aber timetable sein
					)
				)
			),
			'bottom' => array(
				'title' => $this->t('Other'),
				'items' => array(
					'about' => array(
						'title' => $this->t('About…'),
						'class' => '\\TsMobile\\Generator\\Pages\\About',
						'type' => 'html'
					),
					'change_password' => array(
						'title' => $this->t('Passwort ändern'),
						'type' => 'change_password'
					),
					'logout' => array(
						'title' => $this->t('Logout'),
						'type' => 'logout'
					)
				)
			)			
		);
		
		return $aPages;
	}
	
    /**
	 * Liefert den ISO-Code für die Übersetzungen in der App
	 * 
	 * @return string
	 */
	public function getInterfaceLanguage() {

		// Wenn Methode bereits ausgeführt wurde, diesen Wert zurückliefern
		if($this->sInterfaceLanguage !== null) {
			return $this->sInterfaceLanguage;
		}

		if(!$this->_oController->checkAuth()) {
			// Ohne Authorisierung kann die Schule des Benutzers nicht ermittelt werden
			// Daher die erstbeste Schule holen
			$oSchool = \Ext_Thebing_School::getFirstSchool();
		} else {
			$oSchool = $this->getSchool();
		}

		// Browsersprache des Benutzers
		$sLanguage = $this->_oController->getRequest()->get('language_iso');

		// Language-Region-Format (de-DE etc.)
		if(strlen($sLanguage) === 5) {
			$sLanguage = substr($sLanguage, 0, 2);
		}

		// Da in $sLanguage alles mögliche drin stehen kann,
		//	muss mit den Korrespondenzsprachen der Schule verglichen werden!
		$sLanguage = $oSchool->getInterfaceLanguage($sLanguage);

		// Fallback: EN
		if(!$sLanguage) {
			$sLanguage = 'en';
		}

		$this->sInterfaceLanguage = $sLanguage;

		// Locale setzen, damit strftime im System korrekt mit Sprache der App arbeitet
		$aLanguageLocales = \System::getLanguageLocales();
		
		$sLanguageLocale = substr($sLanguage, 0, 2);

		if(isset($aLanguageLocales[$sLanguageLocale])) {
			$sLocale = $aLanguageLocales[$sLanguageLocale];
		} else {
			// Fallback: EN
			$sLocale = $aLanguageLocales['en'];
		}

		// Locale direkt setzen, da System::setLocale() im Frontend nur mit CMS-Seite arbeiten kann
		setlocale(LC_TIME, $sLocale);

		return $this->sInterfaceLanguage;
	}

	/**
	 * Liefert die Schule für die Daten geholt werden sollen
	 * 
	 * @return \Ext_Thebing_School 
	 */
	public function getSchool() {
		return $this->_oSchool;
	}
	
	/**
	 * Liefert das Objekt des angemeldeten Benutzers
	 * 
	 * @return \Ext_Thebing_Teacher|\Ext_TS_Inquiry_Contact_Abstract
	 */
	public function getUser() {
		return $this->_oUser;
	}
			
	/**
	 * Bindet den angemeldeten Benutzer an das Data-Model
	 * 
	 * @param \Ext_Thebing_Teacher|\Ext_TS_Inquiry_Contact_Abstract $oUser
	 */
	public function setUser($oUser) {
		$this->_oUser = $oUser;
	}
	
	/**
	 * Liefert den Typ der App
	 * 
	 * @return string
	 */
	public function getType() {
		return $this->_sType;
	}
	
	/**
	 * Holt eine Übersetzung aus den Frontend-Übersetzungen der Installation
	 * 
	 * @param string $sTranslation
	 * @return string
	 */
	public function t($sTranslation) {
		$sLanguage = $this->getInterfaceLanguage();
		return \Ext_TC_Placeholder_Abstract::translateFrontend($sTranslation, $sLanguage);
	}
	
	/**
	 * Verifiziert den User anhand der E-Mail-Adresse
	 * 
	 * @param type $sEmail
	 * @return null|object
	 */
	public function verifyUserByEmail($sEmail) {
		return null;
	}
	
	/**
	 * Speichert den Zugangscode zu einem User
	 * 
	 * @param string $sAccessCode
	 * @return boolean
	 */
	public function saveAccessKey($sAccessCode) {
		return true;
	}
	
	/**
	 * Liefert die Zugangsdaten des Users anhand des Zugangscode
	 * 
	 * Rückgabewert:
	 * - username
	 * - password
	 * 
	 * @param string $sAccessCode
	 * @return array
	 */
	public function getUserDataByAccessCode($sAccessCode) {
		return array();
	}
	
	/**
	 * sendet dem Kunden den generierten Zugangscode
	 * 
	 * @param string $sEmail
	 * @param string $sAccessCode
	 * @return boolean
	 */
	public function sendAccessCode($sEmail, $sAccessCode) {
		return false;
	}
	
	/**
	 * Prüft ob das Passwort zu dem aktuellen Benutzer passt
	 * 
	 * @param string $sPassword
	 * @return boolean
	 */
	public function checkPassword($sPassword) {
		return false;
	}
	
	/**
	 * Ändert das Passwort für den aktuellen Benutzer
	 * 
	 * @param string $sPassword
	 * @return boolean
	 */
	public function changePassword($sPassword) {
		return false;
	}

	/**
	 * Seiten filtern, welche aktiviert/deaktiviert sind
	 *
	 * @param array $aPages
	 * @return array
	 */
	public function filterPages(array $aPages) {
		return $aPages;
	}

	/**
	 * Factory-Methode zum Erzeugen einer App-Instanz ohne Frontend
	 *
	 * @param \Ext_Thebing_School $oSchool
	 * @return static
	 * @throws \Exception
	 */
	public static function getBackendInstance(\Ext_Thebing_School $oSchool) {

		$oController = new ApiController('', '', '');
		$oApp = new static($oController);
		$oApp->sInterfaceLanguage = \Ext_TC_System::getInterfaceLanguage();
		$oApp->_oSchool = $oSchool;

		// Interface wird im ApiController-Constructor auf "frontend" gesetzt! Muss zurück gesetzt werden.
		\System::setInterface('backend');

		return $oApp;
	}

}

