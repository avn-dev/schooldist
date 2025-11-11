<?php

namespace Licence\Service\Office\Api\Object;

class BillingsForPeriod extends \Licence\Service\Office\Api\AbstractObject {
	
	private $oDatePeriod;
	
	public function __construct(\Core\DTO\DateRange $oDatePeriod) {		
		$this->oDatePeriod = $oDatePeriod;
	}

	public function getUrl() {
		return '/customer/api/billing/period';
	}
	
	/**
	 * Alle nötigen Request-Parameter setzen
	 * 
	 * @param \Licence\Service\Office\Api\Request $oRequest
	 */
	public function prepareRequest(\Licence\Service\Office\Api\Request $oRequest) {
		$oRequest->add('from', $this->oDatePeriod->from->format('Y-m-d'));
		$oRequest->add('until', $this->oDatePeriod->until->format('Y-m-d'));
	}

}

