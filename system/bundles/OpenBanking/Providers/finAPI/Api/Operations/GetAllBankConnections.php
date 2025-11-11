<?php

namespace OpenBanking\Providers\finAPI\Api\Operations;

use OpenBanking\Providers\finAPI\Api\Models\BankConnection;
use OpenBanking\Providers\finAPI\Api\Models\User;
use Api\Interfaces\ApiClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

/**
 * https://docs.finapi.io/#get-/api/v2/bankConnections
 */
class GetAllBankConnections implements UserOperation
{
	public function __construct(
		private readonly User $user
	) {}

	public function getUser(): User
	{
		return $this->user;
	}

	public function send(ApiClient $http, PendingRequest $request): Collection|Response
	{
		$response = $request
			->asJson()
			->get('/api/v2/bankConnections');

		if ($response->successful()) {
			return Collection::make($response->json('connections'))
				->map(fn (array $connection) => new BankConnection(
					$connection['id'],
					$connection['updateStatus'],
					$connection['accountIds'],
				));
		}

		return $response;
	}

}