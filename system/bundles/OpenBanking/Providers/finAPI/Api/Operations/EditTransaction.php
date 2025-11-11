<?php

namespace OpenBanking\Providers\finAPI\Api\Operations;

use OpenBanking\Providers\finAPI\Api\Models\User;
use Api\Interfaces\ApiClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

/**
 * https://docs.finapi.io/#patch-/api/v2/transactions/-id-
 */
class EditTransaction implements UserOperation
{
	public function __construct(
		private readonly User $user,
		private readonly int $transactionId,
		private readonly array $payload
	) {}

	public function getUser(): User
	{
		return $this->user;
	}

	public function send(ApiClient $http, PendingRequest $request): Collection|Response
	{
		return $request
			->asJson()
			->patch('/api/v2/transactions/'.$this->transactionId, $this->payload);
	}
}