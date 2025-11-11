<?php

namespace OpenBanking\Providers\finAPI\Api\Models;

use Carbon\Carbon;
use Illuminate\Support\Arr;

class Webform
{
	public function __construct(
		private readonly string $id,
		private readonly string $url,
		private readonly Carbon $expiration
	) {}

	public function getId(): string
	{
		return $this->id;
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function isExpired(): string
	{
		return Carbon::now() > $this->expiration;
	}

}