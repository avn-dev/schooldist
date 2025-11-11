<?php

namespace OpenBanking\Providers\finAPI\Api\Operations\Token;

use OpenBanking\Providers\finAPI\Api\Models\User;
use OpenBanking\Providers\finAPI\Exceptions\ApiException;

/**
 * https://docs.finapi.io/#post-/api/v2/oauth/token
 */
class RequestUserToken extends RequestToken
{
	public function __construct(
		string $clientId,
		string $clientSecret,
		private readonly User $user
	) {
		parent::__construct($clientId, $clientSecret);
	}

	protected function getGrantType(): string
	{
		return 'password';
	}

	protected function buildPayload(): array
	{
		if (empty($this->user->getPassword())) {
			throw (new ApiException('Missing user password for access token'))->operation($this);
		}

		return [
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret,
			'username' => $this->user->getUsername(),
			'password' => $this->user->getPassword(),
		];
	}
}