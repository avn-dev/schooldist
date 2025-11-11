<?php

namespace OpenBanking\Providers\finAPI\Api\Operations;

use Api\Interfaces\ApiClient\Operation;
use OpenBanking\Providers\finAPI\Api\Models\User;

interface UserOperation extends Operation
{
	public function getUser(): User;
}