<?php

namespace Licence\Exception;

use Licence\Service\Office\Api\Response;

class ApiException extends \RuntimeException {

	private $response;

	public function setResponse(Response $response) {
		$this->message .= sprintf(' (%s)', $response->get('message'));
		$this->response = $response;
		return $this;
	}

	public function getResponse(): ?Response {
		return $this->response;
	}

}
