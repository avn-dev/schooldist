<?php

use Core\Handler\CookieHandler;

class Access {
	
	protected $_sLastMessage = '';
	protected $_sLastErrorCode = '';
	
	protected $_sUser;
	protected $_sPass;
	
	protected $_sAccessUser;
	protected $_sAccessPass;

	protected $_aUserData = array();
	
	protected $_bValidAccess = false;

	protected $_bExecuteLogin = false;

	protected $dLifetime = null;

	/**
	 * Standard: Cookie (Login-Session nach Browser-Schliessen löschen)
	 * @var int
	 */
	protected $iCookieExpire = 0;

	/**
	 * @var self
	 */
	static private $oInstance;

	/**
	 *
	 * @var DB 
	 */
	protected $_oDb;

	public function __construct($oDb) {
		
		$this->_oDb = $oDb;
		
		Access::setInstance($this);

	}

	
	
	static public function getLogger($channel = 'Log') {
		return Log::getLogger('access', $channel);
	}

	static public function setInstance(self $oInstance) {

		self::$oInstance = $oInstance;
		
	}
	
	/**
	 * Instanz in statische Variable setzen
	 */
	public function __wakeup() {

		Access::setInstance($this);
		$this->_oDb = DB::getDefaultConnection();
		
	}

	public function __sleep(): array
    {
        $vars = array_keys(get_object_vars($this));
        // _oDb entfernen
        $vars = array_diff($vars, ['_oDb']);

        return $vars;
    }

	/**
	 * Magic getter
	 * @param string $sName
	 * @return mixed
	 * @throws Exception 
	 */
	public function __get($sName) {

		if(isset($this->_aUserData['data'][$sName])) {
			return $this->_aUserData['data'][$sName];
		} elseif(isset($this->_aUserData[$sName])) {
			return $this->_aUserData[$sName];
		} else {
			throw new Exception('No access to property "'.$sName.'"!');
		}
		
	}

	public function __isset($sName) {
		try {
			$this->__get($sName);
			return true;
		} catch(Exception $e) {
			return false;
		}
	}

	/**
	 * Setzt eine Lebenszeit des Objects auf fünf Minuten
	 * 
	 * @param int $iMinutes
	 */
	public function setTmpLifetime(int $iMinutes=5) {
		
		$dLifetime = new DateTime('now +'.$iMinutes.' min');
		
		$this->setLifetime($dLifetime);
		
		Access::setInstance($this);

	}
	
	/**
	 * 
	 * @param DateTime $dLifetime
	 */
	public function setLifetime(DateTime $dLifetime=null) {

		$this->dLifetime = $dLifetime;

	}

	/**
	 * Prüft Gültigkeit des Objects
	 * 
	 * @return boolean
	 */
	public function isValid() {
		
		if(
			$this->dLifetime === null ||
			$this->dLifetime > new DateTime
		) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Prüft, ob der Login gecheckt werden soll
	 * @return boolean
	 */
	public function checkExecuteLogin() {

		return $this->_bExecuteLogin;

	}
	
	/**
	 * Setzt Werte in globels Array wenn User im Backend eingeloggt ist 
	 */
	public function setData() {
		
	}
	
	public function getAccessUser() {
		return $this->_sAccessUser;
	}
	
	public function getAccessPass() {
		return $this->_sAccessPass;
	}
	
	public function getLastErrorCode() {
		return $this->_sLastErrorCode;
	}
	
	public function checkValidAccess() {

		if(
			$this->_bValidAccess &&
			$this->isValid()
		) {
			$this->_aUserData['login'] = 1;
			return true;
		}

		return false;
	}
	
	/**
	 * Zertört alle Logindaten
	 */
	public function destroyAccess() {

		$this->_bValidAccess = false;
		$this->_aUserData = array();

		$this->_bExecuteLogin = false;

	}

	public function logout() {
		$this->deleteAccessData();
		$this->destroyAccess();
	}
	
	protected function _getCacheKey() {

		$sCacheKey = static::getCacheKey($this->_sAccessUser);
		return $sCacheKey;
	}

	/**
	 * @param string $sAccessUser
	 * @return string
	 */
	public static function getCacheKey(string $sAccessUser) {
		return 'access_'.$sAccessUser;
	}



	protected function _checkAccess($sAccessUser, $sAccessPass) {
		
		$this->_sAccessUser = $sAccessUser;
		$this->_sAccessPass = $sAccessPass;

		$aAccess = $this->getAccessData();

		if($aAccess['password'] === $sAccessPass) {
			return $aAccess;
		}

		return null;
	}
	
	protected function getAccessData() {

		$sCacheKey = $this->_getCacheKey();

		$aAccess = WDCache::get($sCacheKey, true);
		
		return $aAccess;
	}


	public function getUserData() {
		
		return $this->_aUserData;
		
	}

	public function reworkUserData(&$aUserData) {
		
		
		
	}
	
	/**
	 * Prüft, ob der User das Recht hat
	 * @param mixed $mRight 
	 */
	public function hasRight($mRight) {
		
		return false;
		
	}

	/**
	 * Zur Abwärtskompatiblität zum alten Framework 
	 * @return static
	 */
	public static function getInstance() {

		if(
			self::$oInstance !== null &&
			self::$oInstance instanceof Access &&
			self::$oInstance->checkValidAccess()
		) {
			return self::$oInstance;
		}
		
		return null;
	}
		
	public function deleteAccessData() {

		if(!empty($this->_sAccessUser)) {
			$sCacheKey = $this->_getCacheKey();
			WDCache::delete($sCacheKey, true);
		}
		
		CookieHandler::remove($this->getPassCookieName());
		CookieHandler::remove($this->getUserCookieName());

		Core\Handler\SessionHandler::getInstance()->invalidate();

		System::wd()->executeHook('logout', $this->_aUserData);

		$this->destroyAccess();
		
	}

	public function getPassCookieName() {

		if($this instanceof Access_Backend) {

			return 'passcookie';

		} else {

			return 'frontend_passcookie';
		}
	}

	public function getUserCookieName() {

		if($this instanceof Access_Backend) {

			return 'usercookie';

		} else {

			return 'frontend_usercookie';
		}
	}

	public function saveAccessData($iValid = null, array $additionalData = []) {
		
		if($this->_sAccessPass === null) {
			$this->_sAccessPass = $this->generatePasscookie();
		}
		
		if($iValid === null) {
			$iValid = time()+(System::d('session_time') * 60);
		}

		$sCacheKey = $this->_getCacheKey();

		$aData = [
			'ip' => $_SERVER['REMOTE_ADDR'], 
			'username' => $this->_sAccessUser, 
			'password' => $this->_sAccessPass, 
			'valid' => $iValid,
			...$additionalData
		];

		if($this->iCookieExpire !== 0) {
			$aData['persistent_login'] = true;
		}

		if($this instanceof Access_Backend) {
			$aData['backend'] = 1;
		} else {
			$aData['backend'] = 0;
		}

		WDCache::set($sCacheKey, $iValid, $aData, true);

		// Login nur per HTTPS möglich wg. Datenschutz und Sicherheit
		CookieHandler::set($this->getPassCookieName(), $this->_sAccessPass, $this->iCookieExpire, true, true);
		CookieHandler::set($this->getUserCookieName(), $this->_sAccessUser, $this->iCookieExpire, true, true);

	}

	public function generatePasscookie() {
		if(
			System::d('securitylevel') == "low" && 
			CookieHandler::is($this->getPassCookieName())
		) {

			$sPassword = CookieHandler::get($this->getPassCookieName());
			// sicherheit hoch
			// sessionpasswort neu

		} else {

			$sPassword = Util::generateRandomString(32);
		}

		return $sPassword;
	}
	
}
