<?php

namespace Core\Service\Cache\Driver;

abstract class AbstractDriver {

	/**
	 * Schreibt den Eintrag in den Cache und setzt Flags für Cachegruppen und permanente Einträge
	 * 
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param mixed $mData
	 * @param string $sCacheGroup
	 * @return int
	 */
	final public function put($sKey, $iExpiration, $mData, $sCacheGroup = null) {
		
		$iStatus = $this->add($sKey, $iExpiration, $mData, $sCacheGroup);
		
		$this->garbageCollector();
		
		if(!is_null($sCacheGroup)) {
			// Eintrag in der Cachegruppe merken
			$this->addCacheGroupKey($sCacheGroup, $sKey);
		}

		return $iStatus;
		
//		return (new CacheEntry($sKey, $mData))
//				->expiration($iExpiration)
//				->group($sCacheGroup)
//				->date((new \DateTime()))
//				->setStatus($iStatus);
	}

	protected function garbageCollector() {}
	
	/**
	 * Fügt einen Cachekey einer Cachegruppe hinzu
	 * 
	 * @param string $sCacheGroup
	 * @param string $sKey
	 */
	final public function addCacheGroupKey($sCacheGroup, $sKey) {
		
		$aGroupKeys = $this->get($sCacheGroup);

		if(!is_array($aGroupKeys)) {
			$aGroupKeys = [];
		}

		$aGroupKeys[$sKey] = 1;

		$this->add($sCacheGroup, 2419200, $aGroupKeys);
		
	}
	
	/**
	 * Prüft ob der Eintrag im Cache existiert
	 * 
	 * @return bool
	 */
	public function exists($sKey) {
		return !is_null($this->get($sKey));
	}
		
	/**
	 * Liefert einen Eintrag aus dem Cache
	 * 
	 * @return mixed
	 */
	abstract public function get($sKey);
	
	/**
	 * Fügt einen Eintrag dem Cache hinzu
	 * 
	 * @param string $sKey
	 * @param int $iExpiration
	 * @param mixed $mData
	 * @return int
	 */
	abstract protected function add($sKey, $iExpiration, $mData);
	
	/**
	 * Löscht einen Eintrag anhand des Keys aus dem Cache
	 * 
	 * @param string $sKey
	 * @return bool
	 */
	abstract public function forget($sKey);
	
	abstract public function getStats();
	
	/**
	 * Liefert alle Keys die im Cache existieren
	 * 
	 * @return array
	 */
	abstract public function getExistingKeys($sPrefix);

	abstract public function increment(string $key, int $value, int $initialValue = 0, int $expiry = 0);
	
}

