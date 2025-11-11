<?php

namespace OpenBanking\Providers\finAPI\Api\Operations;

use Api\Interfaces\ApiClient;
use OpenBanking\Providers\finAPI\Api\Models\Account;
use OpenBanking\Providers\finAPI\Api\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

/**
 * https://docs.finapi.io/#get-/api/v2/accounts
 */
class GetAccounts implements UserOperation
{
	public function __construct(
		protected readonly User $user
	) {}

	public function getUser(): User
	{
		return $this->user;
	}

	public function send(ApiClient $http, PendingRequest $request): mixed
	{
		$response = $request
			->asJson()
			->get('/api/v2/accounts');

		if ($response->successful()) {
			return Collection::make($response->json('accounts'))
				->map(fn (array $account) => $this->buildAccountFromPayload($account))
				// TODO z.b. Paypal - erstmal rausgenommen, muss man testen
				->filter(fn (Account $account) => !empty($account->getIban()));
		}

		return $response;
	}

	protected function buildAccountFromPayload(array $payload): Account
	{
		return new Account(
			$payload['bankConnectionId'],
			$payload['id'],
			$payload['iban'] ?? '',
			$payload['accountName'] ?? $payload['accountHolderName']
		);
	}

}