<?php

namespace OpenBanking\Providers\finAPI\Api\Operations;

use Api\Interfaces\ApiClient;
use Illuminate\Http\Client\PendingRequest;
use OpenBanking\Providers\finAPI\Api\Models\User;

/**
 * https://docs.finapi.io/#get-/api/v2/accounts/-id-
 */
class GetAccount extends GetAccounts
{
	public function __construct(
		User $user,
		private readonly int $id
	) {
		parent::__construct($user);
	}

	public function send(ApiClient $http, PendingRequest $request): mixed
	{
		$response = $request
			->asJson()
			->get('/api/v2/accounts/'.$this->id);

		if ($response->successful()) {
			$account = $response->json();
			return $this->buildAccountFromPayload($account);
		}

		return $response;
	}


}