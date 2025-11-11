<?php

namespace Core\Handler;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @method mixed get(string $sKey)
 * @method void set(string $sKey, $mValue)
 * @method bool has(string $sKey)
 * @method mixed remove(string $sKey)
 * @method array all()
 * @method mixed invalidate()
 * @method string getId()
 * @method string getName()
 * @method \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface getFlashBag()
 */
class SessionHandler {
	
	/**
	 * @var self
	 */
	private static $oInstance;

	/**
	 * @var bool
	 */
	private static $bDisableSession = false;

	/**
	 * @var Session
	 */
	private $oSession;
	
	private function __construct() {
		$this->oSession = new Session();
//		$this->oSession->start();
	}

	/**
	 * Instanz holen und SESSION STARTEN
	 * @return self
	 */
	public static function getInstance() {

		if(
			self::$bDisableSession &&
			session_status() !== PHP_SESSION_ACTIVE
		) {
			// Es darf nicht die Symfony-Session verwendet werden, da getBag einfach eine Session startet
			return new AttributeBag();
		}

		if(self::$oInstance === null) {
			self::$oInstance = new self();
			self::$oInstance->oSession->start();
		}
		
		return self::$oInstance;

	}

	/**
	 * Session deaktivieren, da diese von Symfony IMMER gestartet wird, wenn irgendwas auf dem Session-Objekt passiert
	 */
	public static function disableSession() {
		self::$bDisableSession = true;
	}

	/**
	 * @param string $sMethod
	 * @param array $aParameters
	 * @return mixed
	 */
	public function __call($sMethod, $aParameters) {
		return call_user_func_array([$this->oSession, $sMethod], $aParameters);
	}

}
