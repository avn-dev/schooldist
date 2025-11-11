<?php

namespace OpenBanking\Providers\finAPI\Api\Models;

use Core\Traits\HasAdditionalData;

class User
{
	use HasAdditionalData;

	public function __construct(
		private readonly string $username,
		private readonly string $password
	) {}

	/**
	 * @return string
	 */
	public function getUsername(): string
	{
		return $this->username;
	}

	/**
	 * @return string
	 */
	public function getPassword(): string
	{
		return $this->password;
	}

}