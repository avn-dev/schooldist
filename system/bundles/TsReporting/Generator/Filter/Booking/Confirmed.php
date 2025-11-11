<?php

namespace TsReporting\Generator\Filter\Booking;

use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Confirmed extends AbstractFilter
{
	public function getTitle(): string
	{
		return $this->t('Bestätigt');
	}

	public function getType(): string
	{
		return 'select';
	}

	public function build(QueryBuilder $builder)
	{
		match ($this->value) {
			'no' => $builder->where('ts_i.confirmed', 0),
			'yes' => $builder->where('ts_i.confirmed', '>', 0),
		};
	}

	public function getOptions(ValueHandler $valueHandler): array
	{
		return [
			['key' => 'no', 'label' => $this->t('Nein')],
			['key' => 'yes', 'label' => $this->t('Ja')],
		];
	}

	public function getDefault(): string
	{
		return 'yes';
	}
}