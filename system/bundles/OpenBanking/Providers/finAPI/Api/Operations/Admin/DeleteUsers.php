<?php

namespace OpenBanking\Providers\finAPI\Api\Operations\Admin;

use Api\Interfaces\ApiClient;
use OpenBanking\Providers\finAPI\Api\Models\User;
use OpenBanking\Providers\finAPI\Api\Operations\ClientOperation;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

/**
 * https://docs.finapi.io/#post-/api/v2/mandatorAdmin/deleteUsers
 */
class DeleteUsers implements ClientOperation
{
	public function __construct(
		private readonly Collection $usernames
	){}

	public function send(ApiClient $http, PendingRequest $request): Response
	{
		return $request
			->asJson()
			->post('/api/v2/mandatorAdmin/deleteUsers', [
				'userIds' => $this->usernames->toArray()
			]);
	}
}