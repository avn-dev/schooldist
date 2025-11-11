<?php

namespace Core\Proxy;

use WDBasic;

abstract class WDBasicAbstract {
	
	/**
	 * @var string
	 */
	protected $sEntityClass = '';
	
	/**
	 * @var WDBasic
	 */
	protected $oEntity;
	
	/**
	 * @param WDBasic $oEntity
	 * @throws \InvalidArgumentException
	 */
	final public function __construct(WDBasic $oEntity) {
		
		if(!$oEntity instanceof $this->sEntityClass) {
			throw new \InvalidArgumentException('Wrong entity passed to proxy ('.get_class($oEntity).').');
		}

		$this->oEntity = $oEntity;

	}
	
	/**
	 * Nur ein Wrapper, damit man im Template Instanzen erzeugen kann
	 * 
	 * @param WDBasic $oEntity
	 * @return \static
	 */
	public static function getInstance(WDBasic $oEntity) {
		
		$oInstance = new static($oEntity);
		
		return $oInstance;
	}

	/**
	 * Gibt eine Objekteigenschaft zurück
	 * @param string $sName
	 * @return null|string
	 */
	public function getProperty($sName) {

		$aData = $this->oEntity->aData;

		if(isset($aData[$sName])) {
			return $aData[$sName];
		}

	}
	
}