<?php

namespace Core\Service;

/**
 * Statischer Key-Value-Storage
 * Speichert zusätzlich im persistenten Cache und ruft diesen ab
 */
class StaticCache {
	
	private $aCache = [];
	private $iExpiration = 3600;
	
	static private $oInstance;
	
	private function __construct() {
		
	}
	
	/**
	 * @return self
	 */
	public static function getInstance() {
		
		if(self::$oInstance === null) {
			self::$oInstance = new self;
		}
		
		return self::$oInstance;
	}
	
	public function setExpiration($iExpiration) {
		$this->iExpiration = $iExpiration;
	}

	/**
	 * @param string $sKey
	 * @return type
	 */
	public function get($sKey) {

		if(isset($this->aCache[$sKey])) {
			return $this->aCache[$sKey];
		}

		return \WDCache::get($sKey);
	}
	
	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public function set($sKey, $mValue) {

		$this->aCache[$sKey] = $mValue;

		\WDCache::set($sKey, $this->iExpiration, $mValue);

	}
	
}