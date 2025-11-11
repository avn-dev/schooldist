<?php

namespace Licence\Service\Office\Api;

class Request {
	
	private $sMethod;
	
	private $sUrl;
	
	private $aData = [];
	
	public function __construct($sUrl, $sMethod = 'POST') {
		$this->sUrl = $sUrl;
		$this->sMethod = strtoupper($sMethod);
	}
	
	public function add($sKey, $mData) {
		$this->aData[$sKey] = $mData;
	}
	
	public function getUrl() {
		
		if($this->isGetRequest()) {
			return $this->sUrl.'?'.http_build_query($this->aData);
		}
		
		return $this->sUrl;
	}
	
	public function getData() {
		return $this->aData;
	}
	
	public function getMethod() {
		return $this->sMethod;
	}
	
	public function isPostRequest() {
		return ($this->getMethod() === 'POST');
	}
	
	public function isGetRequest() {
		return ($this->getMethod() === 'GET');
	}
}

