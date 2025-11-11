<?php

namespace Api\Interfaces;

use Api\Interfaces\ApiClient\Operation;
use Psr\Http\Client\ClientInterface;

interface ApiClient
{
	public function isSandboxed(): bool;

	public function request(Operation $operation);
}