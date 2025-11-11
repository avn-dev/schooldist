<?php

namespace Admin\Interfaces\Component;

use Admin\Dto\Component\Parameters;
use Admin\Instance;
use Admin\Interfaces\RouterAction;

interface RouterActionSource
{
	public static function getRouterActionByKey(Instance $admin, string $key, Parameters $parameters = null, bool $initialize = true): ?RouterAction;
}