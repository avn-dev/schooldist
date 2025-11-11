<?php

namespace TsReporting\Generator\Scopes;

use TsReporting\Generator\Bases\AbstractBase;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

/**
 * Ähnlich zu Laravel Scopes, aber eigenene Implementierung, da Laravel Scopes nur mit Models funktionieren
 */
abstract class AbstractScope
{
	protected AbstractBase $base;

	public function setBase(AbstractBase $base): void
	{
		$this->base = $base;
	}

	abstract public function apply(QueryBuilder $builder, ValueHandler $values): void;
}