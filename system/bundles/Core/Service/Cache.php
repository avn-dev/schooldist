<?php

namespace Core\Service;

use Core\Service\Cache\Driver\AbstractDriver;

class Cache {
	
	const LICENSE_PREFIX = 'x';
	
	const CACHE_GROUP_PREFIX = 'CACHE_GROUP_';
	
	const FOREVER_SUFFIX = '_1';
	
	const EXPIRATION_SUFFIX = '_0';

	const STATUS_ERROR = 0;

	const STATUS_ADDED = 1;

	const STATUS_REPLACED = 2;

	/**
	 * The driver object (memcache, file, db, ...)
	 * 
	 * @var \Core\Service\Cache\Driver\AbstractDriver
	 */
	private $oDriver;
	/**
	 * License key for caching key
	 * 
	 * @var string 
	 */
	private $sLicense;
	
	/**
	 * Konstruktor
	 * 
	 * @param \Core\Service\Cache\Driver\AbstractDriver $oDriver
	 * @param string $sLicence
	 */
	public function __construct(AbstractDriver $oDriver, string $sLicence) {
		$this->oDriver = $oDriver;
		$this->sLicense = $sLicence;
	}

	/**
	 * Liefert einen Value anhand des Cache-Keys
	 * 
	 * @param string $sKey
	 * @param bool $bForever
	 * @return mixed
	 */
	public function get(string $sKey, bool $bForever = false) {
		return $this->oDriver->get($this->buildKey($sKey, $bForever));
	}
	
	public function add($sKey, $mData, $iExpiration) {
		return $this->oDriver->add($this->buildKey($sKey), $iExpiration, $mData);
	}
	
	public function delete($sKey) {
		return $this->oDriver->forget($this->buildKey($sKey));
	}
	
	/**
	 * Fügt dem Cache einen neuen Eintrag hinzu 
	 * 
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param mixed $mData
	 * @param string $sCacheGroup
	 * @return int
	 */
	public function put(string $sKey, int $iExpiration, $mData, string $sCacheGroup = null) {

		if(!is_null($sCacheGroup)) {
			$sCacheGroup = $this->buildGroupKey($sCacheGroup);
		}

		return $this->oDriver->put($this->buildKey($sKey), $iExpiration, $mData, $sCacheGroup);
	}
	
	/**
	 * Fügt dem Cache einen dauerhaften Eintrag hinzu
	 * 
	 * @param string $sKey
	 * @param mixed $mData
	 * @return int
	 */
	public function forever(string $sKey, $mData) {
		return $this->oDriver->put($this->buildKey($sKey, true), 0, $mData, null, true);
	}
	
	/**
	 * Liefert einen Eintrag aus dem Cache oder setzt für den Key die Daten aus der Closure
	 * 
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param \Closure $oCallback
	 * @param string $sCacheGroup
	 * @return mixed
	 */
	public function remember(string $sKey, int $iExpiration, \Closure $oCallback, string $sCacheGroup = null) {
		return $this->rememberEntry($sKey, $iExpiration, $oCallback, $sCacheGroup);
	}

	/**
	 * Liefert einen Eintrag aus dem Cache oder setzt für den Key dauerhaft die Daten aus der Closure
	 * 
	 * @param string $sKey
	 * @param \Closure $oCallback
	 * @return mixed
	 */
	public function rememberForever(string $sKey, \Closure $oCallback) {
		return $this->rememberEntry($sKey, 0, $oCallback, null, true);
	}
	
	/**
	 * Liefert einen Eintrag aus dem Cache oder setzt für den Key die Daten aus der Closure
	 * 
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param \Closure $oCallback
	 * @param string $sCacheGroup
	 * @param bool $bForever
	 * @return mixed
	 */
	private function rememberEntry(string $sKey, int $iExpiration, \Closure $oCallback, string $sCacheGroup = null, bool $bForever = false) {

		$sOriginalKey = $sKey;
		$sKey = $this->buildKey($sKey, $bForever);

		$mExistingData = $this->oDriver->get($sKey);

		if($mExistingData !== null) {
            return $mExistingData;
        }

		$mCacheData = $oCallback();

		if($bForever) {
			$this->forever($sOriginalKey, $mCacheData);
		} else {
			$this->put($sOriginalKey, $iExpiration, $mCacheData, $sCacheGroup);
		}	

		return $mCacheData;
	}


	/**
	 * Leert den Cache
	 * 
	 * @return bool
	 */
	public function flush(bool $bAllEntries = false) {

		$aExistingKeys = $this->getExistingKeys($bAllEntries);

		foreach($aExistingKeys as $sExistingKey) {
			$this->oDriver->forget($sExistingKey);
		}
					
		return true;
	}

	/**
	 * Liefert alle existierenden Keys
	 * 
	 * @param bool $bAllEntries
	 * @return array
	 */
	public function getExistingKeys(bool $bAllEntries = false) {
		
		$aAllKeys = $this->oDriver->getExistingKeys($this->buildLicenseKeyPart());
		
		if($bAllEntries === false) {
			$aAllKeys = array_filter($aAllKeys, function($sKey) {
				return (substr($sKey, -2, 2) !== self::FOREVER_SUFFIX);
			});
		}
		
		return $aAllKeys;
	}
	
	/**
	 * Prüft ob ein Eintrag im Cache existiert
	 * 
	 * @return bool
	 */
	public function exists(string $sKey, bool $bForever = false) {
		return $this->oDriver->exists($this->buildKey($sKey, $bForever));
	}
	
	/**
	 * Löscht einen Eintrag aus dem Cache
	 * 
	 * @return bool
	 */
	public function forget(string $sKey, bool $bForever = false) {
		return $this->oDriver->forget($this->buildKey($sKey, $bForever));
	}
	
	/**
	 * Löscht eine komplette Cachegruppe
	 * 
	 * @param string $sCacheGroup
	 * @return boolean
	 */
	public function forgetGroup(string $sCacheGroup) {

		$aGroupKeys = $this->oDriver->get($this->buildGroupKey($sCacheGroup));
		
		if(!is_null($aGroupKeys)) {
			
			if(is_array($aGroupKeys)) {
				foreach(array_keys($aGroupKeys) as $sGroupItemKey) {
					$this->oDriver->forget($sGroupItemKey);
				}
			}

			$this->oDriver->forget($this->buildGroupKey($sCacheGroup));
			
			return true;			
		}
		
		return false;
	}
	
	public function getStats() {
		return $this->oDriver->getStats();
	}
	
	/**
	 * Gruppenkey erzeugen mit Prefix, damit er möglichst nicht mit normalen Keys verwechselt werden kann
	 *
	 * @param string $sGroup
	 * @return string
	 */
	private function buildGroupKey(string $sGroup) {
		return $this->buildKey(self::CACHE_GROUP_PREFIX.$sGroup);
	}
	
	/**
	 * Build the caching key
	 *
	 * @param string $sKey
	 * @param bool $bForever
	 * @return string
	 */
	private function buildKey(string $sKey, bool $bForever = false) {
		
		$sNewKey = $this->buildLicenseKeyPart().'_'.md5($sKey);
		
		if($bForever) {
			$sNewKey .= self::FOREVER_SUFFIX; // persistent
		} else {
			$sNewKey .= self::EXPIRATION_SUFFIX; // nicht persistent
		}
		
		return $sNewKey;
	}
	
	/**
	 * @return string
	 */
	private function buildLicenseKeyPart() {
		// x + Licence da die Lizenz nicht rein aus Zahlen bestehen darf
		return md5(self::LICENSE_PREFIX.$this->sLicense);
	}
	
	/**
	 * @return Core\Service\Cache\Driver\AbstractDriver
	 */
	private function getDriver() {
		return $this->oDriver;
	}
	
	/**
	 * Protect the __clone()-Method
	 */
	private function __clone() {}

	public function increment(string $key, int $value, int $initalValue = 0, int $expiry = 0, bool $forever = false) {
		return $this->oDriver->increment($this->buildKey($key, $forever), $value, $initalValue, $expiry);
	}
}
