<?php

namespace TsReporting\Generator\Groupings\Booking;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class SalesPerson extends AbstractGrouping
{
	protected string $label;

	public function getTitle(): string
	{
		return $this->t('Vertriebsmitarbeiter');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->addSelect('ts_i.sales_person_id as '.$this->buildSelectFieldId());
		$builder->selectRaw("IF(ts_i.sales_person_id != 0, CONCAT(su.lastname, ', ', su.firstname), '') as ".$this->buildSelectFieldLabel());
		$builder->leftJoin('system_user as su', 'su.id', 'ts_i.sales_person_id');
		$builder->groupBy($this->buildSelectFieldId());
	}
}