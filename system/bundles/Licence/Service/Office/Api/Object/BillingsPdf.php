<?php

namespace Licence\Service\Office\Api\Object;

class BillingsPdf extends \Licence\Service\Office\Api\AbstractObject {
	
	private $iDocumentId;
	
	public function __construct(int $iDocumentId) {		
		$this->iDocumentId = $iDocumentId;
	}

	public function getUrl() {
		return '/customer/api/billing/pdf';
	}
	
	/**
	 * Alle nötigen Request-Parameter setzen
	 * 
	 * @param \Licence\Service\Office\Api\Request $oRequest
	 */
	public function prepareRequest(\Licence\Service\Office\Api\Request $oRequest) {
		$oRequest->add('id', $this->iDocumentId);
	}

}

