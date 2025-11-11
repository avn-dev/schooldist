<?php

namespace Deepl\Api;

use Deepl\Api\Response;
use Deepl\Api\Request;

abstract class AbstractObject {
	
	public function getRequestMethod() {
		return 'POST';
	}
	
	public function prepareResponse(Response $oResponse) {
		return $oResponse;
	}
	
	abstract public function getUrl();
	
	abstract public function prepareRequest(Request $oRequest);
	
}

