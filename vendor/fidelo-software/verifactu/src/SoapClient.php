<?php

namespace FideloSoftware\Verifactu;

class SoapClient extends \SoapClient {
	public string $lastRequestXML;

	public function __doRequest(string $request, string $location, string $action, int $version, bool|int $oneWay = false): null|string
	{
		// Save the raw XML that would have been sent
		$this->lastRequestXML = $request;

		return parent::__doRequest($request, $location, $action, $version, $oneWay);
	}
}