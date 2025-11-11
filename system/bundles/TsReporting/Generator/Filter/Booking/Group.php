<?php

namespace TsReporting\Generator\Filter\Booking;

use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Group extends AbstractFilter
{
	public function getTitle(): string
	{
		return $this->t('Gruppenbuchungen');
	}

	public function getType(): string
	{
		return 'select';
	}

	public function build(QueryBuilder $builder)
	{
		match ($this->value) {
			'individual' => $builder->where('ts_i.group_id', 0),
			'group' => $builder->where('ts_i.group_id', '>', 0),
		};
	}

	public function getOptions(ValueHandler $valueHandler): array
	{
		return [
			['key' => 'individual', 'label' => $this->t('Nur Buchungen ohne Gruppe')],
			['key' => 'group', 'label' => $this->t('Nur Gruppenbuchungen')],
		];
	}
}