<?php

namespace TsReporting\Generator\Filter\Booking;

use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class BookingType extends AbstractFilter
{
	public function getTitle(): string
	{
		return $this->t('Buchungstyp');
	}

	public function getType(): string
	{
		return 'select';
	}

	public function build(QueryBuilder $builder)
	{
		match ($this->value) {
			'direct' => $builder->where('ts_i.agency_id', 0),
			'agency' => $builder->where('ts_i.agency_id', '>', 0),
		};
	}

	public function getOptions(ValueHandler $valueHandler): array
	{
		return [
			['key' => 'direct', 'label' => $this->t('Nur Direktbuchungen')],
			['key' => 'agency', 'label' => $this->t('Nur Agenturbuchungen')],
		];
	}
}