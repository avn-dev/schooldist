<?php

namespace OpenBanking\Providers\finAPI\Api\Operations;

use Api\Interfaces\ApiClient;
use OpenBanking\Providers\finAPI\Api\Models\Account;
use OpenBanking\Providers\finAPI\Api\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * https://docs.finapi.io/#delete-/api/v2/users
 */
class DeleteAccount implements UserOperation
{
	public function __construct(
		private readonly User $user,
		private readonly Account $account
	) {}

	public function getUser(): User
	{
		return $this->user;
	}

	public function send(ApiClient $http, PendingRequest $request): Response
	{
		return $request->delete('/api/v2/accounts/'.$this->account->getId());
	}
}