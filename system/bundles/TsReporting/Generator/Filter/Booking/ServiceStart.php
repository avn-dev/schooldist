<?php

namespace TsReporting\Generator\Filter\Booking;

use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Traits\TimeframeFilterTrait;
use TsReporting\Services\QueryBuilder;

class ServiceStart extends AbstractFilter
{
	use TimeframeFilterTrait;

	public function getTitle(): string
	{
		return $this->t('Leistungsbeginn');
	}

	public function getType(): string
	{
		return 'timeframe';
	}

	public function build(QueryBuilder $builder)
	{
		$builder->whereBetween('service_from', [$this->value->getStartDate(), $this->value->getEndDate()]);
	}
}