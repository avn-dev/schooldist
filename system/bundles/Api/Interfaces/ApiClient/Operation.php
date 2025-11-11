<?php

namespace Api\Interfaces\ApiClient;

use Api\Interfaces\ApiClient;
use Illuminate\Http\Client\PendingRequest;

interface Operation
{
	public function send(ApiClient $http, PendingRequest $request): mixed;
}