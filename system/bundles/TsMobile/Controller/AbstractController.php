<?php
namespace TsMobile\Controller;

abstract class AbstractController extends \MVC_Abstract_Controller {

	protected $_sInterface = 'frontend';

	/**
	 * Controller hat kein CMS-Recht
	 * @var null
	 */
	protected $_sAccessRight = null;

	/**
	 * Access-Objekt
	 * @var \Access_Frontend
	 */
	protected $_oAccessFrontend = null;

	/**
	 * Markierung, ob der Benutzer für die aktuelle Anfrage bereits authentifiziert
	 * wurde
	 * @var boolean
	 */
	protected $_bAuthentificated = false;

	/**
	 * Erlaubte App-Typen (ID zur Customer-DB für Login)
	 * @var array
	 */
	protected $_aApplications = array(
		'ts_student' => 77,
		'ts_teacher' => 32
	);

	/**
	 * App-Klasse
	 * @var \TsMobile\Service\App\Student|\TsMobile\Service\App\Teacher
	 */
	protected $_oApp = null;

	/**
	 * Methode wird vor der angeforderten Controller-Action ausgeführt
	 */
	public function beforeAction($sAction=null) {
		// Jeder Request hat Zugriff
		header('Access-Control-Allow-Origin: *');
		ini_set('html_errors', 0);

		/*if(\Util::isDebugIP()) {
			error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED | E_STRICT));
			ini_set('display_errors', 1);
		}*/

		// Access-Objekt generieren
		$oDb = \DB::getDefaultConnection();
		$this->_oAccessFrontend = new \Access_Frontend($oDb);

		// Helper-Klasse initialisieren
		$this->_initServiceApp();
	}

	public function __call($sMethod, $arguments) {

		try {
			parent::__call($sMethod, $arguments);
		} catch(\Illuminate\Contracts\Container\BindingResolutionException $oException) {
			/*
			 * Uralte Apps (z.B. LSF) benutzen noch den All-Data-Request und hier gibt es einen
			 * Request headAllDataAction, den es nicht geben sollte. Das löst neuerdings dann
			 * irgendwo in Laravel den folgenden Fehler aus und ist irrelevant:
			 * Target [Illuminate\Contracts\Routing\ResponseFactory] is not instantiable.
			 */
		} catch(\Exception $oException) {

			// Schule muss gesetzt werden, da u.U. sonst keine From-E-Mail gefunden wird (wenn admin_email keine E-Mail ist)
			\Ext_Thebing_Mail::$oSchool = \Ext_Thebing_Client::getFirstSchool();

			$oMail = new \WDMail;
			$oMail->subject = 'TsMobile API Exception: '.$oException->getMessage().' – '.\System::d('domain');
			$oMail->text = print_r($oException, 1);
			$oMail->text .= print_r($this->getRequest(), 1);
			$oMail->send(array('TsMobile@p32.de', 'dg@p32.de'));

			throw $oException;
		}
	}

	/**
	 * initialisiert die App (Student/Teacher)
	 */
	protected function _initServiceApp() {

		if($this->_oRequest->get('app_type') === 'ts_teacher') {
			$sAppClass = '\TsMobile\Service\App\Teacher';
		} else {
			$sAppClass = '\TsMobile\Service\App\Student';
		}

		$this->_oApp = new $sAppClass($this);

		if($this->_oRequest->get('app_type') === 'ts_student') {
			$this->_oApp->setRequestInquiryId((int)$this->_oRequest->input('inquiry_id'));
		}
	}

	/**
	 * Prüft, ob die Anfrage von einem am System angemeldeten Benutzer kommt
	 *
	 * @return boolean
	 * @throws \UnexpectedValueException
	 */
	public function checkAuth() {

		// Wenn die checkAuth() für diese Anfrage bereits erfolgreich ausgeführt wurde kann
		// die Überpfrüfung übersprungen werden
		if($this->_bAuthentificated === true) {
			return true;
		}

		$sLoginUser = $this->_oRequest->get('login_user');
		$sLoginKey = $this->_oRequest->get('login_key');

		if(
			empty($sLoginUser) ||
			empty($sLoginKey)
		) {
			return false;
		}

		// Prüfen, ob App-Type stimmt, ansonsten Exception
		$this->_checkAppType();

		if(!$this->_oAccessFrontend->checkSession($sLoginUser, $sLoginKey)) {
			return false;
		}

		$this->_oApp->setUser($this->_getUser());

		// Ablauf der Session verzögern
		$this->_saveAccessData();

		// Für diese Anfrage wurde der Benutzer bereits authentifiziert
		$this->_bAuthentificated = true;

		return true;
	}

	/**
	 * Setzt die Zugriffsdaten für den aktuellen Benutzer
	 *
	 * @param string $sPassword
	 */
	protected function _saveAccessData() {

		$iValid = 14400; // 4 Stunden

		/*
		 * »login_temporary» ist bei jedem Request (AUẞER Login) vorhanden,
		 * solange »angemeldet bleiben« in der App deaktiviert wurde. Diese
		 * Information wird invertiert mitgeschickt, da diese nur dann
		 * benötigt wird, um die Session zu verlängern. Standard ist auch,
		 * dass der Benutzer in der App angemeldet bleibt.
		 *
		 * »remember_me» existiert NUR beim Login-Request und kommt als
		 * boolscher Wert über application/x-www-form-urlencoded, daher
		 * ist das ein String.
		 */
		if(
			(
				// Beide Params sind nie gleichzeitig vorhanden und Standard ist »angemeldet bleiben«
				!$this->_oRequest->exists('login_temporary') &&
				!$this->_oRequest->exists('remember_me')
			) || (
				// »Angemeldet bleiben«-Checkbox beim Login-Request
				$this->_oRequest->exists('remember_me') &&
				$this->_oRequest->get('remember_me') == 'true'
			)
		) {
			$iValid = 0;
		}

		$this->_oAccessFrontend->saveAccessData($iValid);

		$sAccessPass = $this->_oAccessFrontend->getAccessPass();

		return $sAccessPass;
	}

	/**
	 * Prüft, ob der App-Typ gültig ist
	 *
	 * @return boolean
	 * @throws \UnexpectedValueException
	 */
	protected function _checkAppType() {
		$sAppType = $this->_oRequest->get('app_type');

		if(!isset($sAppType, $this->_aApplications)) {
			throw new \UnexpectedValueException('Unknown App-Type ('.$sAppType.')');
		}

		return true;
	}

	/**
	 * Liefert den am System angemeldeten Benutzer
	 *
	 * @return \Ext_Thebing_Teacher|\Ext_TS_Inquiry_Contact_Abstract
	 */
	protected function _getUser() {

		$aUserData = $this->_oAccessFrontend->getUserData();

		if($this->_oRequest->get('app_type') == 'ts_teacher') {
			$iTeacherId = (int) $aUserData['data']['id'];
			$oUser = \Ext_Thebing_Teacher::getInstance($iTeacherId);
		} else {
			$iContactId = (int) $aUserData['data']['contact_id'];
			$oUser = \Ext_TS_Inquiry_Contact_Traveller::getInstance($iContactId);
		}

		return $oUser;
	}
}