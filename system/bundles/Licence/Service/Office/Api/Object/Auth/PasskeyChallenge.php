<?php

namespace Licence\Service\Office\Api\Object\Auth;

use Licence\Service\Office\Api\AbstractObject;
use Licence\Service\Office\Api\Request;

class PasskeyChallenge extends AbstractObject
{
	public function getUrl()
	{
		return 'customer/api/support/auth/passkeys/challenge';
	}

	public function getRequestMethod()
	{
		return 'POST';
	}

	public function __construct(
		private string $host,
	) {}

	public function prepareRequest(Request $request)
	{
		$request->add('host', $this->host);
	}
}