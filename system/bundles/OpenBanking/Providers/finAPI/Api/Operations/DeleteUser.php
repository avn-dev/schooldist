<?php

namespace OpenBanking\Providers\finAPI\Api\Operations;

use Api\Interfaces\ApiClient;
use OpenBanking\Providers\finAPI\Api\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * https://docs.finapi.io/#delete-/api/v2/users
 */
class DeleteUser implements UserOperation
{
	public function __construct(private readonly User $user) {}

	public function getUser(): User
	{
		return $this->user;
	}

	public function send(ApiClient $http, PendingRequest $request): Response
	{
		return $request->delete('/api/v2/users');
	}
}