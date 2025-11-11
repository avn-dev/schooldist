<?php

/**
 * @deprecated -> \Illuminate\Routing\Controller + Middlewares
 */
abstract class MVC_Abstract_Controller extends \Illuminate\Routing\Controller {

	/**
	 * Pfad zur View-Klasse
	 * @var string
	 * @deprecated 
	 */
	protected $_sViewClass = '\MVC_View';

	/**
	 * @var MVC_View|MVC_View_Smarty
	 */
	protected $_oView;
	
	/**
	 * Migration: Dependency Injection
	 * @deprecated
	 * @var MVC_Request
	 */
	protected $_oRequest;
	
	/**
	 * @var DB
	 */
	protected $_oDb;
	
	/**
	 * @var Access
	 */
	protected $_oAccess;
	
	/**
	 * Default Zugriffsrecht 
	 */
	protected $_sAccessRight = 'control';
	
	protected $_sInterface = 'backend';

	protected $fStartTime;


	/**
	 * Setzt die View-Klasse und prüft den Zugriff falls Recht gesetzt
	 * 
	 * @param string $sExtension
	 * @param string $sController
	 * @param string $sAction 
	 */
	function __construct($sExtension, $sController, $sAction, $oAccess=null) {

		if(!empty($this->_sViewClass)) {
			$sViewClass = $this->_sViewClass;
		} else {
			$sViewClass = 'MVC_View';
		}

		$this->_oView = new $sViewClass($sExtension, $sController, $sAction);

		if($oAccess !== null) {
			$this->_oAccess = $oAccess;
		}
		
		System::setInterface($this->_sInterface);

		
		
	}

	public function setStartTime($fStartTime) {
		$this->fStartTime = $fStartTime;
	}
	
	public function initInterface() {
		
		if(System::getInterface() === 'backend') {
			$this->initBackend();
		} else {
			$this->initFrontend();
		}

		Factory::executeStatic('Util', 'getAndSetTimezone');
		Factory::executeStatic('System', 'setLocale');

	}
	
	/**
	 * @todo Auslagern, Trennen usw.
	 * @see \Core\Middleware\Frontend
	 */
	protected function initFrontend() {
		
		// Für Hooks
		$oWebDynamics = webdynamics::getInstance('frontend');

		$oWebDynamics->getIncludes();

		// Spy Übergabe
		if(isset($_GET[System::d('spy_name')])) {
			$sSpy = $_GET[System::d('spy_name')];
			Core\Handler\SessionHandler::getInstance()->set('frontend_spy', $sSpy);
		}

		//Prüft, ob die Seite von einem externen Link aufgerufen wurde
		if (
			isset($_SERVER['HTTP_REFERER']) && 
			!strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])
		) {
			Core\Handler\SessionHandler::getInstance()->set('frontend_referrer', $_SERVER['HTTP_REFERER']);
		}

		// Get Access object
		$oAccessFrontend = new Access_Frontend($this->_oDb);

		// TODO Das sollte so überhaupt nicht passieren, sondern entweder per Middleware an der entsprechenden Stelle oder im CMS
		// 	Das CMS behandelt das mittlerweile selbst, trotzdem läuft alles andere vom Frontend alter Controller auch hierdurch und nimmt die erstbeste Sprache
		// Frontend-Sprache setzen
		$oCmsHelper = new Cms\Helper\Data();
		
		$aSite = $oCmsHelper->getSiteFromDomain($_SERVER['HTTP_HOST']);

		if(empty($aSite)) {
			$aSites = $oCmsHelper->getSites();
			$aSite = reset($aSites);
		}
		
		$oSite = \Cms\Entity\Site::getInstance($aSite['id']);
		$aPossibleLanguages = $oSite->getLanguages(1);
		
		$sLanguage = reset($aPossibleLanguages);

		// Fallback en, damit bei leerer Tabelle nicht alles abstürzt
		if ($sLanguage === false) {
			$sLanguage = 'en';
		}

		System::setInterfaceLanguage($sLanguage);

		$this->handleFrontendLogin($oAccessFrontend);
		
		$this->setAccessObject($oAccessFrontend);
		
	}

	private function handleFrontendLogin(Access_Frontend $oAccessFrontend) {

		/**
		 * Direct login with access code
		 */
		if(
			$this->_oRequest->exists('t') === true &&
			$this->_oRequest->exists('ac') === true
		) {
			$oAccessFrontend->checkDirectLogin($this->_oRequest->get('t'), $this->_oRequest->get('ac'));
		}

		/**
		 * Manueller Login 
		 */
		$oAccessFrontend->checkManualLogin($this->_oRequest->getAll());

		$bAccessSuccess = false;
		
		// Login prüfen
		if(
			Core\Handler\CookieHandler::is($oAccessFrontend->getPassCookieName()) &&
			Core\Handler\CookieHandler::is($oAccessFrontend->getUserCookieName())
		) {
			$bAccess = $oAccessFrontend->checkSession(Core\Handler\CookieHandler::get($oAccessFrontend->getUserCookieName()), Core\Handler\CookieHandler::get($oAccessFrontend->getPassCookieName()));

			if($bAccess === true) {
				$bAccessSuccess = true;
			}
		}
		
		if($oAccessFrontend->checkExecuteLogin() === true) {
	
			$bAccess = $oAccessFrontend->executeLogin();

			if($bAccess === true) {
				$bAccessSuccess = true;
			}
	
			$sErrorCode = $oAccessFrontend->getLastErrorCode();
			if(!empty($sErrorCode)) {
				// TODO Wenn hier ein CODE zurückkommt (z.B. wrong_data), wird das auch übersetzt
				\Core\Handler\SessionHandler::getInstance()->getFlashBag()->add('error', \L10N::t($sErrorCode));
			}

			$oAccessFrontend->checkPersistentLogin($this->_oRequest);

		}
		
		// Logout
		if(
			$this->_oRequest->exists('logout') === true &&
			$this->_oRequest->get('logout') === 'ok' &&
			$oAccessFrontend->checkValidAccess() === true
		) {
			$oAccessFrontend->deleteAccessData();
			return;
		}
		
		if(
			$bAccessSuccess &&
			$oAccessFrontend->checkValidAccess() === true
		) {
			$oAccessFrontend->saveAccessData();
		}

	}
	
	protected function initBackend() {

		// Für Hooks
		$oWebDynamics = webdynamics::getInstance('backend');

		if(\Core\Handler\CookieHandler::is('systemlanguage')) {
			$sSystemLanguage = \Core\Handler\CookieHandler::get('systemlanguage');
		} else {
			$sSystemLanguage = System::getDefaultInterfaceLanguage();
		}

		\System::setInterfaceLanguage($sSystemLanguage);

		$oWebDynamics->getIncludes();
	
		// Get Access object
		$oAccessBackend = new Access_Backend($this->_oDb);

		// Login prüfen
		if(
			Core\Handler\CookieHandler::is($oAccessBackend->getPassCookieName()) &&
			Core\Handler\CookieHandler::is($oAccessBackend->getUserCookieName())
		) {
			$bAccess = $oAccessBackend->checkSession(Core\Handler\CookieHandler::get($oAccessBackend->getUserCookieName()), Core\Handler\CookieHandler::get($oAccessBackend->getPassCookieName()));

			if($bAccess === true) {

				$oAccessBackend->saveAccessData();

				$aUserData = array();
				$oAccessBackend->reworkUserData($aUserData);
			}
		}


		$this->setAccessObject($oAccessBackend);
		
	}
	
	/**
	 * @param MVC_Request
	 */
	public function setRequest(MVC_Request $oRequest) {
		$this->_oRequest = $oRequest;
	}

	/**
	 * @return MVC_Request
	 */
	public function getRequest() {
		return $this->_oRequest;
	}

	/**
	 * @param DB
	 */
	public function setDatabase($oDb) {
		$this->_oDb = $oDb;
	}	
	
	/**
	 *
	 * @param Access_Backend
	 */
	public function setAccessObject($oAccess) {
		$this->_oAccess = $oAccess;
	}
	
	public function beforeAction($sAction=null) {
	
	}

	public function afterAction($sAction=null) {

	}
	
	public function index() {
		
	}
	
	public function checkAccess() {

		// Zugriff prüfen
		if(!empty($this->_sAccessRight)) {
			if (!$this->_oAccess->checkValidAccess()) {
				throw new ErrorException('Unauthorized');
			}

			if(
				// Wenn Recht gesetzt ist, muss das Recht auch überprüft werden!
				// TODO Recht steht standardmäßig auf control
				!$this->_oAccess instanceof Access_Backend ||
				$this->_oAccess->hasRight($this->_sAccessRight) === false
			) {
				throw new ErrorException('No access to controller!');
			}
		}
		
	}

	/**
	 * Wert dem View übergeben
	 * 
	 * @param string $sName
	 */
	public function set($sName, $mValue) {
		if ($mValue instanceof \Illuminate\Support\Collection) {
			$mValue = $mValue->toArray();
		}
		$this->_oView->set($sName, $mValue);
	}

	public function merge($sName, array $mValue) {
		if ($mValue instanceof \Illuminate\Support\Collection) {
			$mValue = $mValue->toArray();
		}
		$this->_oView->merge($sName, $mValue);
	}

	/**
	 * Wert aus dem View liefern
	 * 
	 * @param string $sName
	 * @return mixed
	 */
	public function get($sName) {
		return $this->_oView->get($sName);
	}	
	
	/**
	 *  outputs view 
	 */
	public function getOutput() {

		try {
			$this->_oView->render();
		} catch(Exception $e) {

			// Wenn nicht 200, hat der Controller selber den Status verändert
			// http_response_code() funktioniert erst ab PHP 5.4
			if($this->_oView->getHTTPCode() === 200) {
				$this->_oView->setHTTPCode(500);
			}
			
			// Beim Debugmodus die Exception nicht unterdrücken
			if(System::d('debugmode')) {
				throw $e;
			}
		}

	}

	/**
	 * ruft falls vorhanden eine Methode auf die aus dem gegebenen Methoden namen + HTTP Method zusammengebaut ist
	 * z.b
	 * getDeineMethode()
	 * putDeineMethode()
	 * ----
	 * @param string $sMethod
	 * @param array $arguments
	 */
	public function __call($sMethod, $arguments) {
		
//		$bToken = $this->_checkToken();
//
//		if($bToken !== false) {

			$sHTTPMethod	= $this->_getHTTPMethod();
			$sHTTPMethod	= strtolower($sHTTPMethod);
			$sMethod		= ucfirst($sMethod);
			$sMethod		= $sHTTPMethod.$sMethod;
			$bLanguage		= $this->_checkInterfaceLanguage();

			// TODO Wofür eine Abhängigkeit nach Sprache im Controller?
			if($bLanguage){
				if(method_exists($this, $sMethod)){
					$oMethod	= new ReflectionMethod($this, $sMethod);
					$oMethod->invokeArgs($this, $arguments);
				} else {
					//$this->_setErrorCode('e0002');
					response('', 400);
				}
			}
			
//		} else {
//			$this->_setErrorCode('e0001', 500, $_SERVER['REMOTE_ADDR']);
//		}

	}

	protected function _getHTTPMethod(){
		$sMethod = 'GET';
		if($this->_oRequest->get('_method')){
			$sMethod = $this->_oRequest->get('_method');
		} else if(!empty($_SERVER['REQUEST_METHOD'])){
			$sMethod = $_SERVER['REQUEST_METHOD'];
		} 
		return $sMethod;
	}

	/**
	 * @TODO Entfernen und Verwendung herausfinden
	 *
	 * @return bool
	 */
	protected function _checkInterfaceLanguage(){
		$sLang = (string)$this->_oRequest->get('_lang');
		if(
			!empty($sLang) && 
			strlen($sLang) == 2
		){
			$sLang = strtolower($sLang);
			System::setInterfaceLanguage($sLang);
		} else if(!empty($sLang)){
			$this->_setError('Invalid Language Parameter');
			return false;
		}
		return true;
	}

	/**
	 * 
	 * @param string $sRouteName
	 * @param array $aParameters
	 * 
	 */
	final public function redirect(string $sRouteName, array $aParameters=[], bool $bPermanent=true) {
		
		$sUrl = Core\Helper\Routing::generateUrl($sRouteName, $aParameters);

		$this->redirectUrl($sUrl, $bPermanent);

	}

	final public function redirectUrl(string $sUrl, bool $bPermanent=true, bool $bQSA=false) {

		if(
			$bQSA === true &&
			!empty($_SERVER['QUERY_STRING'])
		) {
			$aParsedUrl = parse_url($sUrl);
			$sSeparator = ($aParsedUrl['query'] == NULL) ? '?' : '&';
			$sUrl .= $sSeparator.$_SERVER['QUERY_STRING'];
		}

		if($bPermanent === true) {
			header('Location: '.$sUrl, true, 301);
		} else {
			header('Location: '.$sUrl, true, 302);
		}

		die();
	}
	
}
