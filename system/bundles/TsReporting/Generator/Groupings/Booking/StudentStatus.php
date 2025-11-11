<?php

namespace TsReporting\Generator\Groupings\Booking;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class StudentStatus extends AbstractGrouping
{
	protected string $label;

	public function getTitle(): string
	{
		return $this->t('Schülerstatus');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->addSelect('ts_i.status_id as '.$this->buildSelectFieldId());
		$builder->addSelect('kss.text as '.$this->buildSelectFieldLabel());
		$builder->leftJoin('kolumbus_student_status as kss', 'kss.id', 'ts_i.status_id');
		$builder->groupBy($this->buildSelectFieldId());
	}
}