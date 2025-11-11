<?php

namespace TsIvvy\Exceptions;

class FailedException extends RuntimeException {

	private $response = null;

	public function response($response) {
		$this->response = $response;
		return $this;
	}

}
