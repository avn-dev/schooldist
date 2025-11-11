<?php

namespace TsReporting\Generator\Filter\Booking;

use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;
use TsReporting\Traits\CourseFilterTrait;

class CourseOnline extends AbstractFilter
{
	use CourseFilterTrait;

	public function getTitle(): string
	{
		return $this->t('Onlinekurs');
	}

	public function getType(): string
	{
		return 'select';
	}

	public function build(QueryBuilder $builder)
	{
		$this->apply($builder, fn (QueryBuilder $builder) => $builder->where('ktc.online', $this->value === 'yes' ? 1 : 0));
	}

	public function getOptions(ValueHandler $valueHandler): array
	{
		return [
			['key' => 'no', 'label' => $this->t('Nein')],
			['key' => 'yes', 'label' => $this->t('Ja')],
		];
	}
}
