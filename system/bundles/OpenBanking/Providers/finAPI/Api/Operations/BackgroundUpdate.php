<?php

namespace OpenBanking\Providers\finAPI\Api\Operations;

use OpenBanking\Providers\finAPI\Api\Models\BankConnection;
use OpenBanking\Providers\finAPI\Api\Models\User;
use Api\Interfaces\ApiClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;

/**
 * https://docs.finapi.io/#get-/api/v2/transactions
 */
class BackgroundUpdate implements UserOperation
{
	public function __construct(
		private readonly User $user,
		private readonly BankConnection $bankConnection,
	) {}

	public function getUser(): User
	{
		return $this->user;
	}

	public function send(ApiClient $http, PendingRequest $request): Collection|Response
	{
		$host = ($http->isSandboxed())
			? 'https://webform-sandbox.finapi.io'
			: 'https://webform-live.finapi.io';

		$response = $request
			->asJson()
			->post($host.'/api/tasks/backgroundUpdate', [
				'bankConnectionId' => $this->bankConnection->getId()
			]);

		return $response;
	}

}