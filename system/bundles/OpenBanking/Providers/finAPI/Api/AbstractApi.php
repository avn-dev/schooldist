<?php

namespace OpenBanking\Providers\finAPI\Api;

use Api\Interfaces\ApiClient;
use OpenBanking\OpenBanking;
use OpenBanking\Providers\finAPI\Api\Models\AccessToken;
use OpenBanking\Providers\finAPI\Api\Models\User;
use OpenBanking\Providers\finAPI\Exceptions\ApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

abstract class AbstractApi implements ApiClient
{
	const ENDPOINT = 'https://live.finapi.io';
	const SANDBOX_ENDPOINT = 'https://sandbox.finapi.io';

	protected bool $sandbox = false;

	protected ?LoggerInterface $logger = null;

	protected static array $accessTokens = [];

	public function __construct(
		private readonly string $clientId,
		private readonly string $clientSecret
	) {
		$this->validate();
	}

	public function sandboxed(bool $payload = true): static
	{
		$this->sandbox = $payload;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getClientId(): string
	{
		return $this->clientId;
	}

	/**
	 * @return string
	 */
	public function getClientSecret(): string
	{
		return $this->clientSecret;
	}

	/**
	 * @return bool
	 */
	public function isSandboxed(): bool
	{
		return $this->sandbox;
	}


	public function request(ApiClient\Operation $operation, string $requestId = null)
	{
		if (empty($requestId)) {
			$requestId = 'fidelo-' . \Util::generateRandomString(10);
		}

		$host = ($this->isSandboxed()) ? self::SANDBOX_ENDPOINT : self::ENDPOINT;

		$this->logger()->info('Request', ['host' => $host, 'operation' => $operation::class, 'request_id' => $requestId]);

		try {

			$accessToken = null;

			if ($operation instanceof \OpenBanking\Providers\finAPI\Api\Operations\ClientOperation) {
				$accessToken = $this->getClientAccessToken($requestId);
				$this->logger()->info('Access-Token', ['request_id' => $requestId, 'type' => 'client' ]);
			} else if ($operation instanceof \OpenBanking\Providers\finAPI\Api\Operations\UserOperation) {
				$accessToken = $this->getUserAccessToken($user = $operation->getUser(), $requestId);
				$this->logger()->info('Access-Token', ['request_id' => $requestId, 'type' => 'user', 'user' => $user->getUsername() ]);
			}

			$headers = ['X-Request-Id' => $requestId];

			if ($accessToken) {
				$headers['Authorization'] = sprintf('%s %s', $accessToken->getType(), $accessToken->getToken());
			}

			$request = Http::baseUrl($host)
				->withHeaders($headers);

			$response = $operation->send($this, $request);

			if ($response instanceof Response && !$response->successful()) {
				$response->throw();
			}

		} catch (\Throwable $e) {
			$this->logger()->error('Request failed', ['request_id' => $requestId, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine() ]);
			throw (new ApiException('Api request failed', 0, $e))->operation($operation);
		}

		return $response;
	}

	protected function getClientAccessToken(string $requestId = null): AccessToken
	{
		$token = self::$accessTokens[static::class]['client'] ?? null;

		if (!$token) {
			$operation = new \OpenBanking\Providers\finAPI\Api\Operations\Token\RequestClientToken($this->clientId, $this->clientSecret);
			$token = $this->request($operation, $requestId);
		}

		self::$accessTokens[static::class]['client'] = $this->validateToken($token);

		return self::$accessTokens[static::class]['client'];
	}

	protected function getUserAccessToken(User $user, string $requestId = null): AccessToken
	{
		$token = self::$accessTokens[static::class][$user->getUsername()] ?? null;

		if (!$token) {
			$operation = new \OpenBanking\Providers\finAPI\Api\Operations\Token\RequestUserToken($this->clientId, $this->clientSecret, $user);
			$token = $this->request($operation, $requestId);
		}

		self::$accessTokens[static::class][$user->getUsername()] = $this->validateToken($token);

		return self::$accessTokens[static::class][$user->getUsername()];
	}

	protected function validateToken(AccessToken $token): AccessToken
	{
		if ($token->isExpired()) {
			if (empty($refreshToken = $token->getRefreshToken())) {
				throw new ApiException('Access-Token expired');
			}

			$operation = new \OpenBanking\Providers\finAPI\Api\Operations\Token\RefreshToken($this->clientId, $this->clientSecret, $refreshToken);
			$token = $this->request($operation);
		}

		return $token;
	}

	protected function validate()
	{
		$missing = [];
		if (empty($this->clientId)) $missing[] = 'Client-ID';
		if (empty($this->clientSecret)) $missing[] = 'Client-Secret';

		if (!empty($missing)) {
			throw new ApiException(sprintf('Missing finAPI-Keys [%s]', implode(', ', $missing)));
		}
	}

	public function logger(): LoggerInterface
	{
		if (!$this->logger) {
			$this->logger = OpenBanking::logger('finAPI');
		}
		return $this->logger;
	}

	public function setLogger(LoggerInterface $logger): static
	{
		$this->logger = $logger;
		return $this;
	}
}