<?php

namespace Communication\Exceptions\App;

class ApnsGatewayException extends \RuntimeException
{
	public function __construct(
		private array $response
	) {
		parent::__construct('Apns Gateway returned error');
	}

	public function getResponse(): array
	{
		return $this->response;
	}

}