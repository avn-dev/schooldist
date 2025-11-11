<?php

namespace OpenBanking\Providers\finAPI\Api\Operations\Admin;

use OpenBanking\Providers\finAPI\Api\Models\User;
use OpenBanking\Providers\finAPI\Api\Operations\ClientOperation;
use Api\Interfaces\ApiClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * https://sandbox.finapi.io/#get-/api/v2/mandatorAdmin/getUserList
 */
class GetUserList implements ClientOperation
{
	public function send(ApiClient $http, PendingRequest $request): Collection|Response
	{
		$response = $request
			->asJson()
			->get('/api/v2/mandatorAdmin/getUserList');

		if ($response->successful()) {
			return Collection::make($response->json('users'))
				->map(fn (array $userData) => (new User($userData['userId'], ''))->additional(Arr::except($userData, ['userId'])));
		}

		return $response;
	}
}