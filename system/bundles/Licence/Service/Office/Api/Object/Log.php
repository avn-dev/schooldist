<?php

namespace Licence\Service\Office\Api\Object;

use Licence\Service\Office\Api\Request;

class Log extends \Licence\Service\Office\Api\AbstractObject
{
	
	public function __construct(private string $subject, private string $message = '', private string $errorLevel = '')
	{

	}

	public function getUrl() {
		return '/customer/api/log';
	}
	
	public function getRequestMethod() {
		return 'POST';
	}
	
	/**
	 * Alle nötigen Request-Parameter setzen
	 * 
	 * @param Request $request
	 */
	public function prepareRequest(Request $request): void
	{
		$request->add('subject', $this->subject);
		$request->add('message', $this->message);
		$request->add('error_level', $this->errorLevel);
	}

}

