<?php

namespace OpenBanking\Providers\finAPI\Api\Operations\Token;

class RequestClientToken extends RequestToken
{
	protected function getGrantType(): string
	{
		return 'client_credentials';
	}

	protected function buildPayload(): array
	{
		return [
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret
		];
	}
}