<?php

namespace Licence\Service\Office\Api;

use Licence\Service\Office\Api\Response;
use Licence\Service\Office\Api\Request;

abstract class AbstractObject {
	
	public function getRequestMethod() {
		return 'GET';
	}
	
	public function prepareResponse(Response $oResponse) {
		return $oResponse;
	}
	
	abstract public function getUrl();
	
	abstract public function prepareRequest(Request $oRequest);
	
}

