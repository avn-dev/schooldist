<?php

namespace TsReporting\Generator\Bases;

//use Carbon\CarbonPeriod;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;
use TsReporting\Traits\TranslateTrait;

abstract class AbstractBase
{
	use TranslateTrait;

//	protected CarbonPeriod $period;

	abstract public function getTitle(): string;

	abstract public function createQueryBuilder(ValueHandler $values): QueryBuilder;

//	public function setPeriod(CarbonPeriod $period)
//	{
//		$this->period = $period;
//	}
}