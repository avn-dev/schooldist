<?php

namespace TcFrontend\Service;

class FrontendInstance {
	/**
	 * @var string 
	 */
	protected $sInstanceHash;
	/**
	 * @var string 
	 */
	protected $sCombinationKey;
	/**
	 * @var string 
	 */
	protected $sTemplateKey;
	/**
	 * @var array 
	 */
	protected $aAdditional = [];
	/**
	 * @var bool 
	 */
	protected $bStatic = false;
	/**
	 * @var bool
	 */
	protected $bSingleton = false;
	/**
	 * @var string 
	 */
	protected $sSingltonKey = '';
	
	/**
	 * @param string $sCombinationKey
	 * @param string $sTemplateKey
	 */
	public function __construct($sCombinationKey, $sTemplateKey, $sStaticInstanceHash = '') {
		$this->sCombinationKey = $sCombinationKey;
		$this->sTemplateKey = $sTemplateKey;
	}
	
	/**
	 * @return string
	 */
	public function getInstanceHash() {
		if($this->isStatic()) {
			$this->sInstanceHash = $this->sStaticInstanceHash;
		} else if(empty($this->sInstanceHash)) {
			$this->sInstanceHash = \Util::generateRandomString(32);
		}
		
		return $this->sInstanceHash;
	}
	
	/**
	 * @param string $sSingletonKey
	 */
	public function enableSingleton($sSingletonKey) {
		$this->bSingleton = true;
		$this->sSingltonKey = $sSingletonKey;
	}
	
	/**
	 * 
	 */
	public function disableSingleton() {
		$this->bSingleton = false;
		$this->sSingltonKey = '';
	}
	
	/**
	 * @return bool
	 */
	public function isStatic() {
		
		if(!empty($this->sStaticInstanceHash)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return bool
	 */
	public function isSingleton() {
		return $this->bSingleton;
	}
	
	/**
	 * @return string
	 */
	public function getSingletonKey() {
		return $this->sSingltonKey;
	}
	
	/**
	 * @param string $sCombinationKey
	 */
	public function setCombinationKey($sCombinationKey) {
		$this->sCombinationKey = $sCombinationKey;
	}
	
	/**
	 * @return string
	 */
	public function getCombinationKey() {
		return $this->sCombinationKey;
	}
	
	/**
	 * @param string $sTemplateKey
	 */
	public function setTemplateKey($sTemplateKey) {
		$this->sTemplateKey = $sTemplateKey;
	}

	/**
	 * @return string
	 */
	public function getTemplateKey() {
		return $this->sTemplateKey;
	}
	
	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public function __set($sKey, $mValue) {
		$this->aAdditional[$sKey] = $mValue;
	}
	
	/**
	 * @param string $sKey
	 * @return mixed
	 */
	public function __get($sKey) {
		if(isset($this->aAdditional[$sKey])) {
			return $this->aAdditional[$sKey];
		}
		
		return null;
	}
	
}
