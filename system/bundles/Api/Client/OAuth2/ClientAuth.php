<?php

namespace Api\Client\OAuth2;

use Illuminate\Contracts\Support\Arrayable;

class ClientAuth implements Arrayable
{
	public function __construct(
		private readonly string $clientId,
		private readonly string $clientSecret,
	) {}

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

	public function toArray()
	{
		return [
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret
		];
	}

	public static function fromArray(array $payload): static
	{
		if (empty($payload['client_id']) || empty($payload['client_secret'])) {
			throw new \RuntimeException('Missing credentials in payload!');
		}

		return new self(
			$payload['client_id'],
			$payload['client_secret'],
		);
	}
}