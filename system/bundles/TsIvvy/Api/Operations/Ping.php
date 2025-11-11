<?php

namespace TsIvvy\Api\Operations;

use TsIvvy\Api\Request;

/**
 * https://developer.ivvy.com/getting-started/test
 */
class Ping extends AbstractOperation {

	public function getUri(): string {
		return $this->buildUri('test', 'ping');
	}

	public function manipulateRequest(Request $request) {
		$request->set('example', 'body');
	}

}
