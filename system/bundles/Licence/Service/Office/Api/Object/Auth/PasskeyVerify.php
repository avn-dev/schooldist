<?php

namespace Licence\Service\Office\Api\Object\Auth;

use Licence\Service\Office\Api\AbstractObject;
use Licence\Service\Office\Api\Request;

class PasskeyVerify extends AbstractObject
{
	public function getUrl()
	{
		return 'customer/api/support/auth/passkeys/attempt';
	}

	public function getRequestMethod()
	{
		return 'POST';
	}

	public function __construct(
		private string $key,
		private string $host,
		private string $passkey,
	) {}

	public function prepareRequest(Request $request)
	{
		$request->add('key', $this->key);
		$request->add('host', $this->host);
		$request->add('passkey', $this->passkey);
	}
}