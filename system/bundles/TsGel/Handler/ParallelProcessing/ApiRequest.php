<?php

namespace TsGel\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;
use TcApi\Client\Traits\FromQueue;
use TsGel\Api;

class ApiRequest extends TypeHandler
{
	use FromQueue;

	public function getLabel()
	{
		return 'GEL - Api Request';
	}

	public function execute(array $data, $debug = false)
	{
		$operation = $this->getOperation($data);
		Api::default()->request($operation, true);
	}
}