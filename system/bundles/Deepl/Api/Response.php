<?php

namespace Deepl\Api;

class Response {

	private $iHttpStatus = 0;
	
	private $aResponseData = [];
	
	public function __construct(array $aResponseData, $iHttpStatus) {
		$this->aResponseData = $aResponseData;
		$this->iHttpStatus = $iHttpStatus;
	}
	
	public function getHttpStatus() {
		return $this->iHttpStatus;
	}
		
	public function hasError() {
		return ($this->getHttpStatus() !== 200);
	}
		
	public function all() {
		return $this->aResponseData;
	}
	
	public function set($sKey, $mValue) {
		$this->aResponseData[$sKey] = $mValue;
		return $this;
	}
	
	public function get($sKey, $mDefault = null) {
		if(isset($this->aResponseData[$sKey])) {
			return $this->aResponseData[$sKey];
		}
		
		return $mDefault;
	}
	
}

