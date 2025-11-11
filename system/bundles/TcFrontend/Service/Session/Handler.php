<?php

namespace TcFrontend\Service\Session;

use TcFrontend\Service\FrontendInstance;

class Handler {
	/**
	 * @var string
	 */
	const SESSION_GLOBAL_KEY = 'thebing_frontend';
	/**
	 * @var string
	 */
	const SESSION_INSTANCE_KEY = 'instances';
	/**
	 * @var string
	 */
	const SESSION_SINGLETON_KEY = 'singleton';	
	/**
	 * @var array 
	 */
	protected $aSession = [];
	
	/**
	 */
	public function __construct() {
		$this->init();
	}
	
	/**
	 * Setzt einen Wert in die Frontend-Session
	 * 
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public function put($sKey, $mValue) {
		$this->aSession[$sKey] = $mValue;
	}
	
	/**
	 * Liefert einen Wert aus der Frontend-Session
	 * 
	 * @param string $sKey
	 * @return mixed
	 */
	public function get($sKey) {
		if($this->exists($sKey)) {
			return $this->aSession[$sKey];
		}
		
		return null;
	}

	/**
	 * Löscht einen Wert aus der Frontend-Session
	 * 
	 * @param string $sKey
	 */
	public function delete($sKey) {
		unset($this->aSession[$sKey]);
	}	
	
	/**
	 * Prüft ob ein Wert in der Frontend-Session existiert
	 * 
	 * @param string $sKey
	 * @return bool
	 */
	public function exists($sKey) {
		return isset($this->aSession[$sKey]);
	}
	
	/**
	 * Liefert alle Daten aus der Frontend-Session
	 * 
	 * @return array
	 */
	public function all() {
		return $this->aSession;
	}
	
	/**
	 * Falls es für einen Key bereits einen Eintrag gibt dann wird der Value zu dem
	 * Key in der Frontend-Session gemerged
	 * 
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public function merge($sKey, $mValue) {
		
		if($this->exists($sKey) === false) {
			$this->put($sKey, $mValue);
		} else {			
			$aSessionValue = $this->get($sKey);	
			
			if(!is_array($aSessionValue)) {
				$aSessionValue = [$aSessionValue];
			}
			
			if(!is_array($mValue)) {
				$mValue = [$mValue];
			}
			
			$this->put($sKey, array_merge_recursive($aSessionValue, $mValue));
		}
		
	}
	
	/**
	 * Leert die Frontend-Session
	 */
	public function clear() {
		unset($_SESSION[self::SESSION_GLOBAL_KEY]);
		$this->init();		
	}
	
	/**
	 * Initialisierung der Frontend-Session
	 */
	protected function init() {
		
		$this->aSession = [];
		
		if(!isset($_SESSION[self::SESSION_GLOBAL_KEY])) {
			$_SESSION[self::SESSION_GLOBAL_KEY] = [];
		}
		
		$this->aSession = &$_SESSION[self::SESSION_GLOBAL_KEY];
		
		if(!isset($this->aSession[self::SESSION_INSTANCE_KEY])) {
			$this->aSession[self::SESSION_INSTANCE_KEY] = [];
		}
		
		if(!isset($this->aSession[self::SESSION_SINGLETON_KEY])) {
			$this->aSession[self::SESSION_SINGLETON_KEY] = [];
		}
	}
	
	/**
	 * @param \TaFrontend\Service\FrontendInstance $oFrontendInstance
	 */
	public function putFrontendInstance(FrontendInstance $oFrontendInstance) {

		$sInstanceHash = $oFrontendInstance->getInstanceHash();
		
		if($oFrontendInstance->isSingleton()) {
			$this->aSession[self::SESSION_SINGLETON_KEY][$oFrontendInstance->getSingletonKey()] = $sInstanceHash;
		}
		
		$this->aSession[self::SESSION_INSTANCE_KEY][$sInstanceHash] = $oFrontendInstance;
	}
	
	/**
	 * @param \TaFrontend\Service\FrontendInstance $oFrontendInstance
	 */
	public function deleteFrontendInstance(FrontendInstance $oFrontendInstance) {
		
		$sInstanceHash = $oFrontendInstance->getInstanceHash();
		
		if(isset($this->aSession[self::SESSION_INSTANCE_KEY][$sInstanceHash])) {
			unset($this->aSession[self::SESSION_INSTANCE_KEY][$sInstanceHash]);
		}
		
		if($oFrontendInstance->isSingleton()) {
			
			$sSingletonKey = $oFrontendInstance->getSingletonKey();
			
			if(isset($this->aSession[self::SESSION_SINGLETON_KEY][$sSingletonKey])) {
				unset($this->aSession[self::SESSION_SINGLETON_KEY][$sSingletonKey]);
			}
		}
	}
	
	/**
	 * @param string $sSingletonKey
	 * @return \TaFrontend\Service\FrontendInstance
	 */
	public function getSingletonFrontendInstance($sSingletonKey) {

		if(isset($this->aSession[self::SESSION_SINGLETON_KEY][$sSingletonKey])) {
			return $this->getFrontendInstance($this->aSession[self::SESSION_SINGLETON_KEY][$sSingletonKey]);
		}
		
		return null;
	}
	
	public function hasFrontendInstance($sInstanceHash) {
		return isset($this->aSession[self::SESSION_INSTANCE_KEY][$sInstanceHash]);
	}
	
	/**
	 * @param string $sInstanceHash
	 * @return \TaFrontend\Service\FrontendInstance
	 */
	public function getFrontendInstance($sInstanceHash) {
		
		if(!empty($sInstanceHash)) {
			$oFrontendInstance = $this->aSession[self::SESSION_INSTANCE_KEY][$sInstanceHash];
		
			if($oFrontendInstance instanceof FrontendInstance) {
				return $oFrontendInstance;
			}
		}
				
		return null;
	}
}