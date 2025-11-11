<?php

namespace TsReporting\Generator\Filter\Booking;

use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Cancelation extends AbstractFilter
{
	public function getTitle(): string
	{
		return $this->t('Stornierte Buchungen');
	}

	public function getType(): string
	{
		return 'select';
	}

	public function build(QueryBuilder $builder)
	{
		match ($this->value) {
			'no' => $builder->where('ts_i.canceled', '0000-00-00 00:00:00'),
			'yes' => $builder->where('ts_i.canceled', '!=', '0000-00-00 00:00:00'),
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
		return 'no';
	}
}
