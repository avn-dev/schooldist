<?php

namespace Core\Service\Cache;

class CacheEntry {
	
	const STATUS_ERROR = 0;
	const STATUS_ADDED = 1;
	const STATUS_REPLACED = 2;
	
	/**
	 * Cache key
	 * 
	 * @var string 
	 */
	private $sKey = '';	
	/**
	 *	Zeitpunkt des Schreibens
	 * 
	 * @var \DateTime
	 */
	private $dDateTime;	
	/**	 
	 * Dauer das Cachings
	 * 
	 * @var int
	 */
	private $iExpiration = 0;	
	/**
	 * Daten des Eintrages
	 * 
	 * @var mixed 
	 */
	private $mData = null;	
	/**
	 * Status wie der Eintrag in den Cache eingefügt wurde
	 * 
	 * ACHTUNG: Der Status wird nicht mit in den Cache geschrieben da dieser erst nach 
	 *          dem Setzen ermittelt werden kann
	 * 
	 * @see STATUS_ERROR
	 * @see STATUS_ADDED
	 * @see STATUS_REPLACED
	 * 
	 * @var int 
	 */
	private $iStatus = 0;
	/**
	 * Cachegruppe des Eintrages
	 * 
	 * @var string 
	 */
	private $sCacheGroup = '';
	/**
	 * Gibt an ob der cache dauerhaft gilt
	 * 
	 * @var bool 
	 */
	private $bForever = false;
	/**
	 * Cachegruppe des der dauerhaften Einträge
	 * 
	 * @var string 
	 */
	private $sForeverKey = '';
	
	/**
	 * Cache Eintrag
	 * 
	 * @param string $sKey
	 * @param mixed $mData
	 */
	public function __construct($sKey, $mData) {
		$this->sKey = $sKey;
		$this->mData = $mData;
	}
	
	/**
	 * Zeitpunkt des Schreibens 
	 * 
	 * @param \DateTime $dDateTime
	 * @return $this
	 */
	public function date(\DateTime $dDateTime) {
		$this->dDateTime = $dDateTime;
		return $this;
	}
	
	/**
	 * Dauer das Cachings (sek.)
	 * 
	 * @param int $iExpiration
	 * @return $this
	 */
	public function expiration($iExpiration) {
		$this->iExpiration = (int) $iExpiration;
		return $this;
	}
	
	/**
	 * Cachegruppe
	 * 
	 * @param string $sCacheGroup
	 * @return $this
	 */
	public function group($sCacheGroup) {
		$this->sCacheGroup = $sCacheGroup;
		return $this;
	}
	
	/**
	 * Das Caching gilt für immer
	 * 
	 * @param string $sKey
	 * @return $this
	 */
	public function forever($sKey) {
		$this->bForever = true;
		$this->sForeverKey = $sKey;
		return $this;
	}
	
	/**
	 * Überschreibt die Daten des Eintrages
	 * 
	 * @param type $mData
	 * @return $this
	 */
	public function overwrite($mData) {
		$this->mData = $mData;
		return $this;
	}
	
	/**
	 * Liefert den Key mit dem der Eintrag im Cache existiert
	 * 
	 * @return string
	 */
	public function getKey() {
		return $this->sKey;
	}
	
	/**
	 * Prüft ob eine Dauer angegeben wurde
	 * 
	 * @return bool
	 */
	public function hasExpiration() {
		return $this->iExpiration > 0;
	}
	
	/**
	 * Liefert die Dauer des Cachings (sek.)
	 * 
	 * @return int
	 */
	public function getExpiration() {
		return $this->iExpiration;
	}

	/**
	 * Liefert das Ablaufdatum des Eitnrages
	 * 
	 * @return \DateTime|null
	 */
	public function getExpirationDateTime() {
		
		if($this->isNotForever()) {
			return $this->getDateTime()->modify('+'.$this->getExpirationInMinutes().' minutes');
		}
		
		return null;
	}
	
	/**
	 * Liefert die Dauer des Cachings in Minuten
	 * 
	 * @return int
	 */
	public function getExpirationInMinutes() {
		return ($this->hasExpiration()) ? (int) ceil($this->iExpiration/60) : 0;
	}
	
	/**
	 * Liefert die Dauer des Cachings in Stunden
	 * 
	 * @return int
	 */
	public function getExpirationInHours() {
		return ($this->hasExpiration()) ? ($this->getExpirationInMinutes()/60) : 0;
	}
	
	/**
	 * Liefert die Dauer des Cachings in Tagen
	 * 
	 * @return int
	 */
	public function getExpirationInDays() {
		return ($this->hasExpiration()) ? ($this->getExpirationInHours()/60) : 0;
	}
	
	/**
	 * Liefert die restliche Zeit bis der Eintrag im Cache abgelaufen ist
	 * 
	 * @param string $sKey
	 * @return \DateInterval|null
	 */
	public function getRemainingTime() {
		if($this->isNotForever()) {			
			return (new \DateTime())->diff($this->getExpirationDateTime());			
		} 	
		
		return null;
	}
	
	/**
	 * Liefert das Datum des Eintrages
	 * 
	 * @return \DateTime
	 */
	public function getDateTime() {
		return $this->dDateTime;
	}
	
	/**
	 * Liefert den Namen der Cachegruppe in der die dauerhaften Einträge
	 * gespeichert sind
	 * 
	 * @return stríng|null
	 */
	public function getForeverKey() {
		
		if($this->isForever()) {
			return $this->sForeverKey;
		}
		
		return null;
	}
	
	/**
	 * Liefert die Gruppe des Eintrages 
	 * 
	 * @return string|null
	 */
	public function getCacheGroup() {
		
		if($this->hasCacheGroup()) {
			return $this->sCacheGroup;
		}
		
		return null;
	}
	
	/**
	 * Setzt den Status des Eintrages 
	 * 
	 * @param int $iStatus
	 * @return $this
	 */
	public function setStatus($iStatus) {
		$this->iStatus = $iStatus;
		return $this;
	}
	
	/**
	 * Liefert die Daten des Eintrages
	 * 
	 * @return mixed
	 */
	public function getData() {
		return $this->mData;
	}
	
	/**
	 * Gibt an ob der Eintrag dauerhaft gecached werden soll
	 * 
	 * @return bool
	 */
	public function isForever() {
		return $this->bForever;
	}
	
	/**
	 * Gibt an ob der Eintrag eine begrenzte Lebenszeit hat
	 * 
	 * @return bool
	 */
	public function isNotForever() {
		return !$this->isForever();
	}
	
	/**
	 * Prüft ob der Eintrag zu einer Gruppe gehört
	 * 
	 * @return bool
	 */
	public function hasCacheGroup() {
		return !empty($this->sCacheGroup);
	}
	
	/**
	 * Gibt an ob das Hinzufügen des Eintrages gescheitert ist
	 * 
	 * ACHTUNG: Kann erst nach dem Setzen (->put()) abgerufen werden und nicht auf Einträge die
	 *          aus dem Cache geladen werden
	 * 
	 * @return bool
	 */
	public function hasFailed() {
		return ($this->iStatus === self::STATUS_ERROR);
	}
	
	/**
	 * Gibt an ob das Hinzufügen des Eintrages erfolgreich war
	 * 
	 * @return bool
	 */
	public function hasNotFailed() {
		return !$this->hasFailed();
	}
}
