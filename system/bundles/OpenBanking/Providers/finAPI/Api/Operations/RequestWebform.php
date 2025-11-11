<?php

namespace OpenBanking\Providers\finAPI\Api\Operations;

use Api\Interfaces\ApiClient;
use Carbon\Carbon;
use Core\Helper\Routing;
use GuzzleHttp\Psr7\Uri;
use OpenBanking\Providers\finAPI\Api\Models\User;
use OpenBanking\Providers\finAPI\Api\Models\Webform;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * https://docs.finapi.io/#post-/api/webForms/bankConnectionImport
 */
class RequestWebform implements UserOperation
{
	public function __construct(
		private readonly User $user,
		private readonly Uri $callbackUrl
	) {}

	public function getUser(): User
	{
		return $this->user;
	}

	public function send(ApiClient $http, PendingRequest $request): Webform|Response
	{
		$host = ($http->isSandboxed())
			? 'https://webform-sandbox.finapi.io'
			: 'https://webform-live.finapi.io';

		$response = $request
			->asJson()
			->post($host.'/api/webForms/bankConnectionImport', [
				//'accountTypes' => ['SAVINGS'],
				'callbacks' => [
					'finalised' => (string)$this->callbackUrl,
				]
			]);

		if ($response->successful()) {
			$json = $response->json();
			return new Webform($json['id'], $json['url'], Carbon::parse($json['expiresAt']));
		}

		return $response;
	}

}