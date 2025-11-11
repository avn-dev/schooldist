<?php

namespace OpenBanking\Providers\finAPI\Api\Operations\Token;

use OpenBanking\Providers\finAPI\Api\Models\AccessToken;
use Api\Interfaces\ApiClient;
use Api\Interfaces\ApiClient\Operation;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * https://docs.finapi.io/#post-/api/v2/oauth/token
 */
abstract class RequestToken implements Operation
{
	public function __construct(
		protected readonly string $clientId,
		protected readonly string $clientSecret
	) {}

	abstract protected function getGrantType(): string;

	abstract protected function buildPayload(): array;

	public function send(ApiClient $http, PendingRequest $request): AccessToken|Response
	{
		$grantType = $this->getGrantType();

		$response = $request->asForm()
			->post('/api/v2/oauth/token',
				[
					...['grant_type' => $grantType],
					...$this->buildPayload()
				]
			);

		if ($response->successful()) {
			$json = $response->json();

			return new AccessToken(
				$grantType,
				$json['token_type'],
				$json['access_token'],
				$json['expires_in'],
				$json['refresh_token'] ?? null
			);
		}

		return $response;
	}

}