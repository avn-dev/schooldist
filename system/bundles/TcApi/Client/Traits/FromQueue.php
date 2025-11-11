<?php

namespace TcApi\Client\Traits;

use TcApi\Client\Interfaces\Operation;

trait FromQueue
{
	protected function getOperation(array $data): Operation
	{
		$operation = call_user_func_array([$data['operation'], 'fromArray'], [$data['data']]);
		return $operation;
	}
}