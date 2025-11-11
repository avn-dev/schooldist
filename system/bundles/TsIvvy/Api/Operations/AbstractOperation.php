<?php

namespace TsIvvy\Api\Operations;

use TsIvvy\Api;

abstract class AbstractOperation {

	private $logEnabled = false;

	abstract public function getUri(): string;

	public function enableLogging() {
		$this->logEnabled = true;
	}

	protected function buildUri(string $type, string $action): string {
		return sprintf('/api/%s/%s?action=%s', Api::API_VERSION,  $type, $action);
	}

	protected function log($message, array $data) {

		if(!$this->logEnabled) {
			return;
		}

		Api::getLogger()->info($message, $data);
	}

}
