<?php

namespace TsReporting\Generator\Groupings\Booking;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Group extends AbstractGrouping
{
	protected string $field;

	public function getTitle(): string
	{
		return $this->t('Gruppe');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->selectRaw("COALESCE(kg.id, 0) as ".$this->buildSelectFieldId());
		$builder->selectRaw("COALESCE(kg.name, '') as ".$this->buildSelectFieldLabel());
		$builder->leftJoin('kolumbus_groups as kg', 'kg.id', 'ts_i.group_id');
		$builder->groupBy($this->buildSelectFieldId());
	}
}