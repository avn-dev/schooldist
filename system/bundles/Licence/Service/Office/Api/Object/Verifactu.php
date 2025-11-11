<?php

namespace Licence\Service\Office\Api\Object;

class Verifactu extends \Licence\Service\Office\Api\AbstractObject {

	public function __construct(
		private string $operation,
		private string $payload,
		private string $certificate,
		private string $certificatePassword,
		private bool $test = false
	){}

	public function getUrl() {
		return '/customer/api/invoices/verifactu';
	}

	public function getRequestMethod() {
		return 'POST';
	}

	/**
	 * Alle nötigen Request-Parameter setzen
	 *
	 * @param \Licence\Service\Office\Api\Request $oRequest
	 */
	public function prepareRequest(\Licence\Service\Office\Api\Request $oRequest) {
		$oRequest->add('operation', $this->operation);
		$oRequest->add('payload', $this->payload);
		$oRequest->add('test', $this->test);
		$oRequest->add('certificate', $this->certificate);
		$oRequest->add('certificate_password', $this->certificatePassword);
	}

}