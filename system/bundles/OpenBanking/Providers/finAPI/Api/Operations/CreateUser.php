<?php

namespace OpenBanking\Providers\finAPI\Api\Operations;

use Api\Interfaces\ApiClient;
use OpenBanking\Providers\finAPI\Api\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use OpenBanking\Providers\finAPI\Exceptions\ApiException;

/**
 * https://docs.finapi.io/#post-/api/v2/users
 */
class CreateUser implements ClientOperation
{
	private ?string $email = null;
	private ?string $phone = null;

	public function __construct(
		private readonly string $username,
		private readonly string $password,
	) {}

	public function send(ApiClient $http, PendingRequest $request): User|Response
	{
		$this->validate();

		$response = $request
			->asJson()
			->post('/api/v2/users', [
				'id' => $this->username,
				'password' => $this->password,
				'isAutoUpdateEnabled' => true
			]);

		if ($response->successful()) {
			$json = $response->json();
			return new User($json['id'], $json['password']);
		}

		return $response;
	}

	private function validate(): void
	{
		$checkLength = function (string $payload, int $min, int $max) {
			$length = strlen($payload);
			return $length >= $min && $length <= $max;
		};

		if (!$checkLength($this->username, 1, 36) || !preg_match('/[a-zA-Z0-9\-_\.\+@]*/', $this->username)) {
			throw (new ApiException(sprintf('Invalid username for finAPI [%s]', $this->username)))->operation($this);
		}

		if (!$checkLength($this->password, 6, 128)) {
			throw (new ApiException('Invalid password for finAPI'))->operation($this);
		}

		//if (
		//	!empty($this->email) &&
		//	(!$checkLength($this->email, 1, 320) || !preg_match('/[A-Za-z0-9¡-ʯ &\(\)\{\}\[\]\.:,;\?!\+\-_\$@#~`\^€]*/', $this->email))
		//) {
		//	throw (new ApiException(sprintf('Invalid e-mail for finAPI [%s]', $this->email)))->operation($this);
		//}

		//if (
		//	!empty($this->phone) &&
		//	(!$checkLength($this->phone, 1, 50) || !preg_match('/[A-Za-z0-9¡-ʯ &\(\)\{\}\[\]\.:,;\?!\+\-_\$@#~`\^€]*/', $this->phone))
		//) {
		//	throw (new ApiException(sprintf('Invalid phone number for finAPI [%s]', $this->phone)))->operation($this);
		//}
	}
}