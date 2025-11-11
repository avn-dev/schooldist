<?php

namespace OpenBanking\Providers\finAPI\Api\Models;

use Carbon\Carbon;

class AccessToken
{
	private ?Carbon $expiration;

	public function __construct(
		private readonly string $grantType,
		private readonly string $type,
		private readonly string $token,
		int $expiresIn,
		private readonly ?string $refreshToken = null,
	) {
		$this->expiration = Carbon::now()->addSeconds($expiresIn);
	}

	public function getGrantType(): string
	{
		return $this->grantType;
	}

	public function getType(): string
	{
		return ucfirst($this->type);
	}

	public function getToken(): string
	{
		return $this->token;
	}

	public function getRefreshToken(): ?string
	{
		return $this->refreshToken;
	}

	public function isExpired(): bool
	{
		return Carbon::now() > $this->expiration;
	}
}