<?php

namespace OpenBanking\Providers\finAPI\Api\Operations\Token;

/**
 * https://docs.finapi.io/#post-/api/v2/oauth/token
 */
class RefreshToken extends RequestToken
{
	public function __construct(
		string $clientId,
		string $clientSecret,
		private readonly string $refreshToken,
	) {
		parent::__construct($clientId, $clientSecret);
	}

	protected function getGrantType(): string
	{
		return 'refresh_token';
	}

	protected function buildPayload(): array
	{
		return [
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret,
			'refresh_token' => $this->refreshToken
		];
	}
}