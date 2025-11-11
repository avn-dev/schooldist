<?php

namespace TcApi\Client\Interfaces;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

interface Operation
{
	public function send(PendingRequest $request): ?Response;
}