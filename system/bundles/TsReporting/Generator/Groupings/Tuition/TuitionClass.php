<?php

namespace TsReporting\Generator\Groupings\Tuition;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\Scopes\Booking\CourseScope;
use TsReporting\Generator\Scopes\Booking\TuitionScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class TuitionClass extends AbstractGrouping
{
	public function getTitle(): string
	{
		return $this->t('Klasse (mit Schülern)');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->requireScope(CourseScope::class);
		$builder->requireScope(TuitionScope::class);
		$builder->addSelect('ktcl.id as '.$this->buildSelectFieldId());
		$builder->addSelect('ktcl.name as '.$this->buildSelectFieldLabel());
		$builder->groupBy($this->buildSelectFieldId());
	}
}